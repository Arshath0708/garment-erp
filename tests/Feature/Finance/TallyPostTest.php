<?php

namespace Tests\Feature\Finance;

use App\Models\Buyer;
use App\Models\Category;
use App\Models\Currency;
use App\Models\DebitNote;
use App\Models\DocumentFormat;
use App\Models\ExportDocument;
use App\Models\OrderConfirmation;
use App\Models\Supplier;
use App\Models\TallyPostLog;
use App\Models\TallySetting;
use App\Models\User;
use App\Support\FinancialYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TallyPostTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['tally.view', 'tally.edit', 'tally.post', 'export-document.view'] as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $this->user = User::factory()->create();
        $this->user->givePermissionTo(['tally.view', 'tally.edit', 'tally.post', 'export-document.view']);
    }

    public function test_guest_cannot_open_tally_settings(): void
    {
        $this->get(route('finance.tally.settings'))->assertRedirect(route('login'));
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $plain = User::factory()->create();

        $this->actingAs($plain)->get(route('finance.tally.settings'))->assertForbidden();
    }

    public function test_settings_page_and_save(): void
    {
        $this->actingAs($this->user)
            ->get(route('finance.tally.settings'))
            ->assertOk()
            ->assertSee('Tally XML URL', false)
            ->assertSee('GST e-invoice', false);

        $this->actingAs($this->user)
            ->put(route('finance.tally.settings.update'), [
                'is_enabled'              => '1',
                'host_url'                => 'http://127.0.0.1:9000',
                'company_name'            => 'Demo Garments',
                'sales_voucher_type'      => 'Sales',
                'debit_note_voucher_type' => 'Debit Note',
                'sales_ledger'            => 'Export Sales',
                'igst_ledger'             => 'IGST',
                'job_work_ledger'         => 'Job Work Charges',
            ])
            ->assertRedirect(route('finance.tally.settings'));

        $this->assertTrue(TallySetting::current()->is_enabled);
        $this->assertSame('Demo Garments', TallySetting::current()->company_name);
        $this->assertSame('Export Sales', TallySetting::current()->sales_ledger);
    }

    public function test_sales_xml_download_includes_invoice_and_irn(): void
    {
        $document = $this->exportDocument();
        $document->update([
            'gst_irn'    => 'IRN-ABC-001',
            'gst_ack_no' => 'ACK-9',
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('finance.tally.export-documents', $document));

        $response->assertOk();
        $this->assertStringContainsString('application/xml', (string) $response->headers->get('content-type'));
        $xml = $response->streamedContent();
        $this->assertStringContainsString('INV-1001', $xml);
        $this->assertStringContainsString('Test Overseas Buyer', $xml);
        $this->assertStringContainsString('IRN IRN-ABC-001', $xml);
        $this->assertStringContainsString('1000.00', $xml);

        $this->assertDatabaseHas('tally_post_logs', [
            'source_type' => TallyPostLog::SOURCE_EXPORT,
            'source_id'   => $document->id,
            'status'      => 'downloaded',
        ]);
    }

    public function test_post_to_tally_when_enabled(): void
    {
        Http::fake([
            'http://127.0.0.1:9000' => Http::response('<RESPONSE>Created</RESPONSE>', 200),
        ]);

        TallySetting::current()->update([
            'is_enabled' => true,
            'host_url'   => 'http://127.0.0.1:9000',
        ]);

        $document = $this->exportDocument();

        $this->actingAs($this->user)
            ->from(route('export.documents.show', $document))
            ->post(route('finance.tally.export-documents', $document), ['mode' => 'post'])
            ->assertRedirect(route('export.documents.show', $document))
            ->assertSessionHas('success');

        $this->assertSame('posted', TallyPostLog::query()->first()?->status);
        Http::assertSent(fn ($request) => $request->url() === 'http://127.0.0.1:9000'
            && str_contains($request->body(), 'INV-1001'));
    }

    public function test_post_is_blocked_until_tally_is_turned_on(): void
    {
        $document = $this->exportDocument();

        $this->actingAs($this->user)
            ->from(route('export.documents.show', $document))
            ->post(route('finance.tally.export-documents', $document), ['mode' => 'post'])
            ->assertRedirect(route('export.documents.show', $document))
            ->assertSessionHas('warning');
    }

    public function test_gst_irn_saves_on_export_document(): void
    {
        $document = $this->exportDocument();

        $this->actingAs($this->user)
            ->put(route('finance.tally.gst-irn', $document), [
                'gst_irn'      => 'IRN-SAVE-1',
                'gst_ack_no'   => '1122',
                'gst_ack_date' => '2026-09-04',
            ])
            ->assertRedirect(route('export.documents.show', $document));

        $this->assertSame('IRN-SAVE-1', $document->fresh()->gst_irn);
        $this->assertSame('1122', $document->fresh()->gst_ack_no);
    }

    public function test_issued_debit_note_xml_download(): void
    {
        $jobber = Supplier::create([
            'display_code' => 'JBW-TL',
            'party_type'   => 'jobber',
            'company_name' => 'Wash House',
            'status'       => 'active',
        ]);

        $note = new DebitNote([
            'note_date'   => now()->toDateString(),
            'supplier_id' => $jobber->id,
            'amount'      => 40,
            'qty'         => 10,
            'reason'      => 'job_work_damage',
            'status'      => 'issued',
        ]);
        $note->financial_year = FinancialYear::current();
        $note->debit_note_num = 'DN/T/001';
        $note->save();

        $response = $this->actingAs($this->user)
            ->post(route('finance.tally.debit-notes', $note));

        $response->assertOk();
        $xml = $response->streamedContent();
        $this->assertStringContainsString('DN/T/001', $xml);
        $this->assertStringContainsString('Wash House', $xml);
        $this->assertStringContainsString('40.00', $xml);
    }

    private function exportDocument(): ExportDocument
    {
        $buyer = Buyer::forceCreate([
            'display_code' => 'BUY-TL',
            'company_name' => 'Test Overseas Buyer',
            'status'       => 'active',
        ]);
        $category = new Category(['name' => 'Knits', 'status' => 'active']);
        $category->code = 'CAT-TL';
        $category->save();
        $format = DocumentFormat::create(['name' => 'Std', 'status' => 'active']);
        $currency = new Currency(['name' => 'USD', 'symbol' => '$', 'status' => 'active']);
        $currency->iso_code = 'USD';
        $currency->save();

        $oc = new OrderConfirmation([
            'buyer_id'           => $buyer->id,
            'category_id'        => $category->id,
            'document_format_id' => $format->id,
            'currency_id'        => $currency->id,
            'oc_date'            => now()->toDateString(),
            'status'             => 'confirmed',
        ]);
        $oc->oc_num = 'GT/TL/001/'.FinancialYear::current();
        $oc->financial_year = FinancialYear::current();
        $oc->save();

        $document = new ExportDocument([
            'order_confirmation_id' => $oc->id,
            'buyer_id'              => $buyer->id,
            'currency_id'           => $currency->id,
            'invoice_no'            => 'INV-1001',
            'invoice_date'          => now()->toDateString(),
            'status'                => 'draft',
        ]);
        $document->doc_num = 'ED/TL/001';
        $document->financial_year = FinancialYear::current();
        $document->save();
        $document->items()->create([
            'description' => 'Cotton tee',
            'qty'         => 10,
            'price'       => 100,
            'amount'      => 1000,
            'sort_order'  => 0,
        ]);

        return $document->fresh('items');
    }
}
