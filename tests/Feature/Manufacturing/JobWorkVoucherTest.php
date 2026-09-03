<?php

namespace Tests\Feature\Manufacturing;

use App\Models\GarmentStyle;
use App\Models\JobWorkVoucher;
use App\Models\ProductionOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Support\FinancialYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class JobWorkVoucherTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['job-work.view', 'job-work.create', 'job-work.edit', 'job-work.delete', 'debit-note.view', 'debit-note.create'] as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $this->user = User::factory()->create();
        $this->user->givePermissionTo(['job-work.view', 'job-work.create', 'job-work.edit', 'job-work.delete', 'debit-note.view', 'debit-note.create']);
    }

    public function test_guest_cannot_view_job_work_and_user_without_permission_is_forbidden(): void
    {
        $this->get(route('job-work.index'))->assertRedirect(route('login'));

        $plain = User::factory()->create();
        $this->actingAs($plain)->get(route('job-work.index'))->assertForbidden();
    }

    public function test_issue_then_receive_uses_jw_number_and_caps_outstanding(): void
    {
        [$order, $jobber] = $this->orderAndJobber();

        $this->actingAs($this->user)
            ->post(route('job-work.store'), [
                'type'                => 'issue',
                'voucher_date'        => '2026-09-03',
                'jobber_id'           => $jobber->id,
                'production_order_id' => $order->id,
                'sizes'               => ['S' => 10, 'M' => 15],
            ])
            ->assertRedirect();

        $issue = JobWorkVoucher::query()->first();
        $this->assertNotNull($issue);
        $this->assertSame('issue', $issue->type);
        $this->assertSame(25, $issue->total_qty);
        $this->assertSame('JW/'.FinancialYear::current().'/001', $issue->voucher_num);

        $this->actingAs($this->user)
            ->from(route('job-work.create', ['type' => 'receive', 'production_order_id' => $order->id]))
            ->post(route('job-work.store'), [
                'type'                => 'receive',
                'voucher_date'        => '2026-09-04',
                'jobber_id'           => $jobber->id,
                'production_order_id' => $order->id,
                'sizes'               => ['S' => 20, 'M' => 20],
            ])
            ->assertRedirect(route('job-work.create', ['type' => 'receive', 'production_order_id' => $order->id]))
            ->assertSessionHasErrors('sizes');

        $this->actingAs($this->user)
            ->post(route('job-work.store'), [
                'type'                => 'receive',
                'voucher_date'        => '2026-09-04',
                'jobber_id'           => $jobber->id,
                'production_order_id' => $order->id,
                'damaged_qty'         => 2,
                'rate_per_pc'         => 12.5,
                'sizes'               => ['S' => 10, 'M' => 10],
            ])
            ->assertRedirect();

        $receive = JobWorkVoucher::query()->where('type', 'receive')->first();
        $this->assertNotNull($receive);
        $this->assertSame(20, $receive->total_qty);
        $this->assertSame(18, $receive->goodQty());
        $this->assertEquals(225.0, (float) $receive->charge_amount);
        $this->assertSame('JW/'.FinancialYear::current().'/002', $receive->voucher_num);

        $this->actingAs($this->user)
            ->post(route('finance.debit-notes.store'), [
                'job_work_voucher_id' => $receive->id,
            ])
            ->assertRedirect(route('job-work.show', $receive));

        $note = \App\Models\DebitNote::query()->first();
        $this->assertNotNull($note);
        $this->assertEquals(25.0, (float) $note->amount);
        $this->assertSame(2, $note->qty);
        $this->assertSame($jobber->id, $note->supplier_id);
        $this->assertStringStartsWith('DN/', $note->debit_note_num);
    }

    public function test_cannot_delete_issue_after_pieces_are_received(): void
    {
        [$order, $jobber] = $this->orderAndJobber();

        $this->actingAs($this->user)
            ->post(route('job-work.store'), [
                'type'                => 'issue',
                'voucher_date'        => '2026-09-03',
                'jobber_id'           => $jobber->id,
                'production_order_id' => $order->id,
                'sizes'               => ['S' => 10],
            ]);

        $issue = JobWorkVoucher::query()->first();

        $this->actingAs($this->user)
            ->post(route('job-work.store'), [
                'type'                => 'receive',
                'voucher_date'        => '2026-09-04',
                'jobber_id'           => $jobber->id,
                'production_order_id' => $order->id,
                'sizes'               => ['S' => 10],
            ]);

        $this->actingAs($this->user)
            ->from(route('job-work.show', $issue))
            ->delete(route('job-work.destroy', $issue))
            ->assertRedirect(route('job-work.show', $issue))
            ->assertSessionHas('warning');

        $this->assertDatabaseHas('job_work_vouchers', ['id' => $issue->id]);
    }

    /**
     * @return array{0: ProductionOrder, 1: Supplier}
     */
    private function orderAndJobber(): array
    {
        $style = GarmentStyle::create([
            'style_number' => 'ST-JW-'.uniqid(),
            'name'         => 'Job Work Tee',
            'status'       => 'Active',
            'target_qty'   => 100,
        ]);

        $jobber = Supplier::create([
            'display_code' => 'JBW01',
            'party_type'   => 'jobber',
            'company_name' => 'Stitch House',
            'status'       => 'active',
        ]);

        $order = ProductionOrder::create([
            'order_number'     => 'PO-JW-'.uniqid(),
            'garment_style_id' => $style->id,
            'total_qty'        => 100,
            'target_date'      => now()->addDays(10),
            'current_stage'    => 'Cutting',
            'status'           => 'In Progress',
            'job_work_type'    => 'stitching',
            'jobber_id'        => $jobber->id,
        ]);

        return [$order, $jobber];
    }
}
