<?php

namespace Tests\Feature\Export;

use App\Models\Buyer;
use App\Models\Category;
use App\Models\Currency;
use App\Models\DocumentFormat;
use App\Models\ExportDocument;
use App\Models\OrderConfirmation;
use App\Models\User;
use App\Services\Export\ExportDocumentService;
use App\Services\Export\GeminiDocumentExtractor;
use Database\Seeders\DocumentChecklistTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DocumentOcrTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private ExportDocument $document;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed(DocumentChecklistTypeSeeder::class);

        foreach ([
            'export-document.view', 'export-document.create', 'export-document.edit', 'export-document.delete',
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo([
            'export-document.view', 'export-document.create', 'export-document.edit', 'export-document.delete',
        ]);

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

        $oc = new OrderConfirmation([
            'buyer_id'           => $buyer->id,
            'category_id'        => $category->id,
            'document_format_id' => $format->id,
            'currency_id'        => $currency->id,
            'oc_date'            => now()->toDateString(),
            'status'             => 'confirmed',
        ]);
        $oc->oc_num = 'GT/OC/001/26-27';
        $oc->financial_year = '26-27';
        $oc->save();

        $item = $oc->items()->create([
            'sort_order'  => 0,
            'design_no'   => 'D-100',
            'unit'        => 'pcs',
            'price'       => 5.00,
            'qty'         => 100,
            'amount'      => 500.00,
        ]);

        $this->document = app(ExportDocumentService::class)
            ->raiseFromOrderConfirmation($oc, [$item->id]);
        $this->document->load('checklist.type');
    }

    public function test_ocr_desk_page_loads(): void
    {
        $this->actingAs($this->admin)
            ->get(route('export.ocr.index'))
            ->assertOk()
            ->assertSee('Document OCR', false)
            ->assertSee('#4 Checklist', false);
    }

    public function test_ocr_requires_gemini_key(): void
    {
        config(['services.gemini.key' => null]);

        $this->actingAs($this->admin)
            ->postJson(route('export.ocr.extract'), [
                'file'      => UploadedFile::fake()->image('cha.jpg'),
                'type_code' => 'cha_checklist',
            ])
            ->assertStatus(503);
    }

    public function test_ocr_extracts_cha_checklist_fields(): void
    {
        config([
            'services.gemini.key'   => 'test-key',
            'services.gemini.model' => 'gemini-2.5-flash',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                'checklist_no'      => 'CHA/JOB/7788',
                                'checklist_date'    => '2026-08-15',
                                'shipping_bill_no'  => 'SB1234567',
                                'invoice_no'        => 'INV/001',
                                'cha_name'          => 'Demo CHA Pvt Ltd',
                                'status_or_remarks' => 'Filed on ICEGATE',
                            ]),
                        ]],
                    ],
                ]],
            ], 200),
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('export.ocr.extract'), [
                'file'      => UploadedFile::fake()->image('cha.jpg'),
                'type_code' => 'cha_checklist',
            ])
            ->assertOk()
            ->assertJsonPath('reference_no', 'CHA/JOB/7788')
            ->assertJsonFragment(['remarks' => 'Date: 2026-08-15 · SB: SB1234567 · Invoice: INV/001 · CHA: Demo CHA Pvt Ltd · Filed on ICEGATE']);
    }

    public function test_ocr_rejects_non_phase1_type_on_desk(): void
    {
        config(['services.gemini.key' => 'test-key']);

        $this->actingAs($this->admin)
            ->postJson(route('export.ocr.extract'), [
                'file'      => UploadedFile::fake()->image('bl.jpg'),
                'type_code' => 'bl_final',
            ])
            ->assertStatus(422);
    }

    public function test_saving_ocr_updates_checklist_row(): void
    {
        $file = UploadedFile::fake()->create('cha-checklist.pdf', 40, 'application/pdf');

        $this->actingAs($this->admin)
            ->post(route('export.ocr.store'), [
                'export_document_id' => $this->document->id,
                'type_code'          => 'cha_checklist',
                'file'               => $file,
                'reference_no'       => 'CHA/JOB/7788',
                'remarks'            => 'Date: 2026-08-15',
            ])
            ->assertRedirect();

        $entry = $this->document->checklist()
            ->whereHas('type', fn ($q) => $q->where('code', 'cha_checklist'))
            ->firstOrFail();

        $this->assertSame('uploaded', $entry->status);
        $this->assertSame('CHA/JOB/7788', $entry->reference_no);
        $this->assertNotNull($entry->file_path);
    }

    public function test_phase1_only_lists_cha_checklist(): void
    {
        $this->assertSame(['cha_checklist'], GeminiDocumentExtractor::PHASE1_TYPES);
        $this->assertContains('cha_checklist', GeminiDocumentExtractor::UPLOADED_TYPES);
        $this->assertNotContains('packing_list', GeminiDocumentExtractor::UPLOADED_TYPES);
    }
}
