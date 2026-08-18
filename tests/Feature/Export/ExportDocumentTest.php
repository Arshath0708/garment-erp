<?php

namespace Tests\Feature\Export;

use App\Models\Buyer;
use App\Models\Category;
use App\Models\Currency;
use App\Models\DocumentChecklistType;
use App\Models\DocumentFormat;
use App\Models\ExportDocument;
use App\Models\OrderConfirmation;
use App\Models\User;
use Database\Seeders\DocumentChecklistTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ExportDocumentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;
    private OrderConfirmation $oc;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed(DocumentChecklistTypeSeeder::class);

        foreach ([
            'export-document.view', 'export-document.create', 'export-document.edit', 'export-document.delete',
            'export-document.generate', 'order-confirmation.approve', 'packing.view',
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo([
            'export-document.view', 'export-document.create', 'export-document.edit', 'export-document.delete',
            'export-document.generate', 'order-confirmation.approve', 'packing.view',
        ]);

        $this->user = User::factory()->create();

        $buyer = Buyer::forceCreate([
            'display_code' => 'BUY01',
            'company_name' => 'Test Overseas Buyer',
            'status'       => 'active',
        ]);

        $category = new Category(['name' => 'Woven Garments', 'status' => 'active']);
        $category->code = 'CAT001';
        $category->save();

        $format = DocumentFormat::create(['name' => 'Standard Order Format', 'status' => 'active']);

        $currency = new Currency(['name' => 'US Dollar', 'symbol' => '$', 'status' => 'active']);
        $currency->iso_code = 'USD';
        $currency->save();

        $this->oc = new OrderConfirmation([
            'buyer_id'           => $buyer->id,
            'category_id'        => $category->id,
            'document_format_id' => $format->id,
            'currency_id'        => $currency->id,
            'oc_date'            => now()->toDateString(),
            'status'             => 'confirmed',
        ]);
        $this->oc->oc_num = 'GT/OC/001/26-27';
        $this->oc->financial_year = '26-27';
        $this->oc->save();

        $this->oc->items()->create([
            'sort_order'  => 0,
            'design_no'   => 'D-100',
            'description' => 'Cotton T-Shirt Blue L',
            'unit'        => 'pcs',
            'price'       => 5.00,
            'qty'         => 100,
            'amount'      => 500.00,
        ]);
    }

    public function test_guest_cannot_access_export_documents(): void
    {
        $this->get(route('export.documents.index'))
            ->assertRedirect(route('login'));
    }

    public function test_raising_export_document_creates_header_items_and_full_checklist(): void
    {
        $item = $this->oc->items->first();

        $response = $this->actingAs($this->admin)
            ->post(route('sales.order-confirmations.raise-export-document', $this->oc), [
                'item_ids' => [$item->id],
            ]);

        $document = ExportDocument::first();
        $response->assertRedirect(route('export.documents.show', $document));

        $this->assertNotNull($document);
        $this->assertSame($this->oc->id, $document->order_confirmation_id);
        $this->assertSame('draft', $document->status);
        $this->assertCount(1, $document->items);
        $this->assertSame(100, $document->items->first()->qty);

        $expectedRows = DocumentChecklistType::where('status', 'active')
            ->get()
            ->sum(fn (DocumentChecklistType $type) => $type->hasVariants() ? count($type->variant_labels) : 1);

        $this->assertSame($expectedRows, $document->checklist()->count());
        $this->assertTrue($document->checklist()->where('status', 'pending')->count() === $expectedRows);

        $this->assertNotNull($item->fresh()->export_document_id);
        $this->assertNotNull($item->fresh()->shipped_at);
    }

    public function test_cannot_raise_export_document_from_unconfirmed_oc(): void
    {
        $this->oc->update(['status' => 'draft']);
        $item = $this->oc->items->first();

        $this->actingAs($this->admin)
            ->post(route('sales.order-confirmations.raise-export-document', $this->oc), [
                'item_ids' => [$item->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('warning');

        $this->assertSame(0, ExportDocument::count());
    }

    public function test_uploading_a_checklist_document_marks_it_uploaded(): void
    {
        $document = $this->raiseDocument();
        $entry = $document->checklist()->whereHas('type', fn ($q) => $q->where('code', 'assessed_copy'))->firstOrFail();

        $file = UploadedFile::fake()->create('assessed-copy.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->admin)->post(
            route('export.documents.checklist.update', [$document, $entry]),
            ['file' => $file, 'reference_no' => 'REF-001', 'mark_done' => 1]
        );

        $response->assertRedirect();
        $entry->refresh();

        $this->assertSame('uploaded', $entry->status);
        $this->assertSame('REF-001', $entry->reference_no);
        $this->assertNotNull($entry->file_path);
        $this->assertNotNull($entry->uploaded_at);
        $this->assertSame('in_progress', $document->fresh()->status);
    }

    public function test_uploading_ebrc_closes_the_export_document(): void
    {
        $document = $this->raiseDocument();
        $entry = $document->checklist()->whereHas('type', fn ($q) => $q->where('code', 'ebrc'))->firstOrFail();

        $file = UploadedFile::fake()->create('ebrc.pdf', 50, 'application/pdf');

        $this->actingAs($this->admin)->post(
            route('export.documents.checklist.update', [$document, $entry]),
            ['file' => $file, 'mark_done' => 1]
        );

        $this->assertSame('closed', $document->fresh()->status);
    }

    public function test_resetting_a_checklist_row_reverts_to_pending(): void
    {
        $document = $this->raiseDocument();
        $entry = $document->checklist()->whereHas('type', fn ($q) => $q->where('code', 'examination'))->firstOrFail();

        $this->actingAs($this->admin)->post(
            route('export.documents.checklist.update', [$document, $entry]),
            ['mark_done' => 1, 'remarks' => 'Examined ok']
        );
        $this->assertSame('received', $entry->fresh()->status);

        $this->actingAs($this->admin)->delete(route('export.documents.checklist.reset', [$document, $entry]));

        $this->assertSame('pending', $entry->fresh()->status);
    }

    public function test_insurance_option_one_cancels_draft_without_file(): void
    {
        $document = $this->raiseDocument();
        $entry = $document->checklist()->whereHas('type', fn ($q) => $q->where('code', 'insurance'))->firstOrFail();

        $this->actingAs($this->admin)->post(
            route('export.documents.checklist.update', [$document, $entry]),
            ['insurance_action' => 'cancel_draft', 'mark_done' => 1]
        )->assertRedirect()->assertSessionHas('success');

        $entry->refresh();
        $this->assertSame('cancelled', $entry->status);
        $this->assertNull($entry->file_path);
        $this->assertSame('Insurance draft cancelled', $entry->remarks);
    }

    public function test_insurance_option_two_requires_bl_and_certificate_file(): void
    {
        $document = $this->raiseDocument();
        $entry = $document->checklist()->whereHas('type', fn ($q) => $q->where('code', 'insurance'))->firstOrFail();

        $this->actingAs($this->admin)->post(
            route('export.documents.checklist.update', [$document, $entry]),
            [
                'insurance_action' => 'upload_certificate',
                'mark_done'        => 1,
                'bl_number'        => 'MAEU1234567',
            ]
        )->assertSessionHasErrors(['file', 'bl_date']);

        $file = UploadedFile::fake()->create('insurance-cert.pdf', 80, 'application/pdf');

        $this->actingAs($this->admin)->post(
            route('export.documents.checklist.update', [$document, $entry]),
            [
                'insurance_action' => 'upload_certificate',
                'mark_done'        => 1,
                'file'             => $file,
                'bl_number'        => 'MAEU1234567',
                'bl_date'          => '2026-06-15',
                'reference_no'     => 'POL-9988',
            ]
        )->assertRedirect()->assertSessionHas('success');

        $entry->refresh();
        $this->assertSame('uploaded', $entry->status);
        $this->assertSame('POL-9988', $entry->reference_no);
        $this->assertNotNull($entry->file_path);
        $this->assertStringContainsString('B/L: MAEU1234567', (string) $entry->remarks);
        $this->assertStringContainsString('B/L date: 2026-06-15', (string) $entry->remarks);
    }

    public function test_user_without_permission_cannot_raise_or_edit(): void
    {
        $item = $this->oc->items->first();

        $this->actingAs($this->user)
            ->post(route('sales.order-confirmations.raise-export-document', $this->oc), ['item_ids' => [$item->id]])
            ->assertForbidden();

        $document = $this->raiseDocument();
        $entry = $document->checklist()->first();

        $this->actingAs($this->user)
            ->get(route('export.documents.show', $document))
            ->assertForbidden();

        $this->actingAs($this->user)
            ->post(route('export.documents.checklist.update', [$document, $entry]), ['mark_done' => 1])
            ->assertForbidden();
    }

    public function test_generating_delivery_challan_pdf_marks_checklist_generated(): void
    {
        $document = $this->raiseDocument();
        $document->update([
            'forwarder_name' => 'Shrinathji Forwarders',
            'vehicle_no'     => 'MH-01/DR-3197',
            'total_cartons'  => 25,
        ]);

        $response = $this->actingAs($this->admin)->get(route('export.documents.delivery-challan', $document));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));

        $entry = $document->checklist()->whereHas('type', fn ($q) => $q->where('code', 'delivery_challan'))->firstOrFail();
        $this->assertSame('generated', $entry->status);
        $this->assertNotNull($entry->file_path);
        Storage::disk('public')->assertExists($entry->file_path);
    }

    public function test_generating_e_invoice_pdf_marks_checklist_generated(): void
    {
        $document = $this->raiseDocument();

        $response = $this->actingAs($this->admin)->get(route('export.documents.e-invoice', $document));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));

        $entry = $document->checklist()->whereHas('type', fn ($q) => $q->where('code', 'e_invoice'))->firstOrFail();
        $this->assertSame('generated', $entry->status);
        $this->assertNotNull($entry->file_path);
    }

    public function test_generating_remaining_export_pdfs_marks_checklist_generated(): void
    {
        $document = $this->raiseDocument();

        $map = [
            'item_summary'   => 'export.documents.item-summary',
            'export_invoice' => 'export.documents.export-invoice',
            'purchase_bills' => 'export.documents.purchase-bills',
            'vgm'            => 'export.documents.vgm',
            'bank_docs'      => 'export.documents.bank-docs',
            'buyer_docs'     => 'export.documents.buyer-docs',
            'packing_list'   => 'export.documents.packing-list',
            'bl_draft'       => 'export.documents.bl-draft',
        ];

        $rows = $document->checklist()
            ->with('type:id,code')
            ->whereHas('type', fn ($q) => $q->whereIn('code', array_keys($map)))
            ->get();

        $this->assertNotEmpty($rows);

        foreach ($rows as $row) {
            $code = $row->type->code;
            $variant = $row->variant_code;
            $url = $variant
                ? route($map[$code], [$document, $variant])
                : route($map[$code], $document);

            $response = $this->actingAs($this->admin)->get($url);
            $this->assertSame(200, $response->status(), "Failed generating {$code} {$variant}: HTTP {$response->status()} {$url}");
            $this->assertSame('application/pdf', $response->headers->get('content-type'));

            $row->refresh();
            $this->assertSame('generated', $row->status, "{$code} {$variant} was not marked generated");
        }

        $this->actingAs($this->admin)
            ->get(route('export.packing.show', $document))
            ->assertOk()
            ->assertSee($document->doc_num);
    }

    public function test_editing_export_document_saves_shipment_logistics_fields(): void
    {
        $document = $this->raiseDocument();

        $response = $this->actingAs($this->admin)->put(route('export.documents.update', $document), [
            'invoice_no'         => 'EXP25269001',
            'invoice_date'       => '2025-04-08',
            'exporter_ref'       => 'GC/BS/146/24',
            'forwarder_name'     => 'Shrinathji Forwarders',
            'forwarder_address'  => 'CWC Distripark, Sector 7, Dronagiri Node',
            'vehicle_no'         => 'MH-01/DR-3197',
            'total_cartons'      => 25,
            'package_kind'       => 'CARTONS',
        ]);

        $response->assertRedirect(route('export.documents.show', $document));
        $document->refresh();
        $this->assertSame('EXP25269001', $document->invoice_no);
        $this->assertSame(25, $document->total_cartons);
        $this->assertSame('Shrinathji Forwarders', $document->forwarder_name);
    }

    private function raiseDocument(): ExportDocument
    {
        $item = $this->oc->items->first();

        $this->actingAs($this->admin)->post(route('sales.order-confirmations.raise-export-document', $this->oc), [
            'item_ids' => [$item->id],
        ]);

        return ExportDocument::firstOrFail();
    }
}
