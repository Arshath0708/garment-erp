<?php

namespace Tests\Feature\Sales;

use App\Models\Buyer;
use App\Models\Category;
use App\Models\Currency;
use App\Models\DocumentFormat;
use App\Models\ExportDocument;
use App\Models\Inquiry;
use App\Models\OrderConfirmation;
use App\Models\User;
use Database\Seeders\DocumentChecklistTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class OfferToInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DocumentChecklistTypeSeeder::class);

        foreach ([
            'order-confirmation.create',
            'order-confirmation.edit',
            'order-confirmation.approve',
            'export-document.create',
            'export-document.view',
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }
    }

    public function test_one_click_from_enquiry_creates_confirmed_oc_and_export_document(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['order-confirmation.create', 'export-document.create', 'export-document.view']);

        [$inquiry] = $this->makeInquiryWithConfirmedItem();

        $this->actingAs($user)
            ->post(route('sales.inquiries.convert-to-invoice', $inquiry))
            ->assertRedirect();

        $oc = OrderConfirmation::query()->first();
        $this->assertNotNull($oc);
        $this->assertSame('confirmed', $oc->status);
        $this->assertSame($inquiry->id, $oc->source_inquiry_id);

        $doc = ExportDocument::query()->first();
        $this->assertNotNull($doc);
        $this->assertSame($oc->id, $doc->order_confirmation_id);
        $this->assertNotNull($doc->invoice_no);
        $this->assertSame($inquiry->buyer_ref, $doc->buyer_ref_no);

        $this->assertNotNull($oc->items()->first()->export_document_id);
    }

    public function test_one_click_from_draft_oc_confirms_and_raises_invoice(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([
            'export-document.create',
            'export-document.view',
            'order-confirmation.edit',
        ]);

        [$inquiry, $buyer, $category, $format, $currency] = $this->makeInquiryWithConfirmedItem();

        $oc = new OrderConfirmation([
            'mode'               => 'oc',
            'oc_date'            => now()->toDateString(),
            'buyer_ref'          => 'PO-INV-1',
            'source_inquiry_id'  => $inquiry->id,
            'buyer_id'           => $buyer->id,
            'category_id'        => $category->id,
            'document_format_id' => $format->id,
            'currency_id'        => $currency->id,
            'status'             => 'draft',
        ]);
        $oc->oc_num = 'GT/OC/INV/26-27';
        $oc->financial_year = '26-27';
        $oc->save();
        $oc->items()->create([
            'sort_order'  => 0,
            'design_no'   => 'D-INV',
            'description' => 'Tee',
            'unit'        => 'pcs',
            'qty'         => 20,
            'price'       => 4,
            'amount'      => 80,
        ]);

        $this->actingAs($user)
            ->post(route('sales.order-confirmations.raise-invoice', $oc))
            ->assertRedirect();

        $this->assertSame('confirmed', $oc->fresh()->status);
        $this->assertDatabaseCount('export_documents', 1);
        $this->assertNotNull($oc->items()->first()->fresh()->export_document_id);
    }

    /**
     * @return array{0: Inquiry, 1: Buyer, 2: Category, 3: DocumentFormat, 4: Currency}
     */
    private function makeInquiryWithConfirmedItem(): array
    {
        $buyer = Buyer::forceCreate([
            'display_code' => 'BUYINV',
            'company_name' => 'Invoice Path Buyer',
            'status'       => 'active',
        ]);
        $category = new Category(['name' => 'Knits', 'status' => 'active']);
        $category->code = 'CATINV';
        $category->save();
        $format = DocumentFormat::create(['name' => 'Inv Format', 'status' => 'active']);
        $currency = new Currency(['name' => 'US Dollar', 'symbol' => '$', 'status' => 'active']);
        $currency->iso_code = 'USD';
        $currency->save();

        $inquiry = Inquiry::forceCreate([
            'inquiry_no'             => 'INQ-INV-1',
            'financial_year'         => '2026-27',
            'buyer_id'               => $buyer->id,
            'category_id'            => $category->id,
            'document_format_id'     => $format->id,
            'currency_id'            => $currency->id,
            'inquiry_date'           => now()->toDateString(),
            'buyer_ref'              => 'BUYER-PO-99',
            'expected_shipment_date' => '2026-12-01',
            'remarks'                => 'One click path',
            'status'                 => 'confirmed',
        ]);
        $inquiry->items()->create([
            'sort_order'  => 0,
            'design_no'   => 'D-INV',
            'description' => 'Tee',
            'unit'        => 'pcs',
            'qty'         => 50,
            'price'       => 5,
            'amount'      => 250,
            'status'      => 'confirmed',
        ]);

        return [$inquiry, $buyer, $category, $format, $currency];
    }
}
