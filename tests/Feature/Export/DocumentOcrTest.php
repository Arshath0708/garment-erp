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
            ->assertSee('Checklist (from CHA)', false)
            ->assertSee('E-Sanchit Documents', false)
            ->assertSee('Assessed Copy', false)
            ->assertSee('LEO Copy', false)
            ->assertSee('CLP', false)
            ->assertSee('Bill of Lading (Final)', false)
            ->assertSee('Insurance Certificate', false)
            ->assertSee('eBRC', false);
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

    public function test_ocr_extracts_e_sanchit_fields(): void
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
                                'ack_or_ref_no'     => 'ESAN/ACK/91',
                                'document_date'     => '2026-08-14',
                                'invoice_no'        => 'GT/EXP/001/2026-27',
                                'packing_list_ref'  => 'GT/PL/001/2026-27',
                                'shipping_bill_no'  => 'SB-7845123',
                                'exporter_name'     => 'Guru Traders',
                                'status_or_remarks' => 'Ready for ICEGATE upload',
                            ]),
                        ]],
                    ],
                ]],
            ], 200),
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('export.ocr.extract'), [
                'file'      => UploadedFile::fake()->image('esan.jpg'),
                'type_code' => 'e_sanchit_docs',
            ])
            ->assertOk()
            ->assertJsonPath('reference_no', 'ESAN/ACK/91')
            ->assertJsonFragment([
                'remarks' => 'Date: 2026-08-14 · Invoice: GT/EXP/001/2026-27 · PL: GT/PL/001/2026-27 · SB: SB-7845123 · Exporter: Guru Traders · Ready for ICEGATE upload',
            ]);
    }

    public function test_ocr_extracts_assessed_and_leo_fields(): void
    {
        config([
            'services.gemini.key'   => 'test-key',
            'services.gemini.model' => 'gemini-2.5-flash',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push([
                    'candidates' => [[
                        'content' => [
                            'parts' => [[
                                'text' => json_encode([
                                    'assessed_ref_no'    => 'ASC/NS/1',
                                    'assessed_date'      => '2025-04-09',
                                    'shipping_bill_no'   => 'SB-1',
                                    'invoice_no'         => 'INV-1',
                                    'examiner_or_office' => 'Nhava Sheva Customs',
                                    'status_or_remarks'  => 'Passed for stuffing',
                                ]),
                            ]],
                        ],
                    ]],
                ], 200)
                ->push([
                    'candidates' => [[
                        'content' => [
                            'parts' => [[
                                'text' => json_encode([
                                    'leo_number'        => 'LEO/NS/1',
                                    'leo_date'          => '2025-04-10',
                                    'shipping_bill_no'  => 'SB-1',
                                    'invoice_no'        => 'INV-1',
                                    'port_of_loading'   => 'Nhava Sheva',
                                    'status_or_remarks' => 'LEO granted',
                                ]),
                            ]],
                        ],
                    ]],
                ], 200),
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('export.ocr.extract'), [
                'file'      => UploadedFile::fake()->image('assessed.jpg'),
                'type_code' => 'assessed_copy',
            ])
            ->assertOk()
            ->assertJsonPath('reference_no', 'ASC/NS/1')
            ->assertJsonFragment([
                'remarks' => 'Date: 2025-04-09 · SB: SB-1 · Invoice: INV-1 · Office: Nhava Sheva Customs · Passed for stuffing',
            ]);

        $this->actingAs($this->admin)
            ->postJson(route('export.ocr.extract'), [
                'file'      => UploadedFile::fake()->image('leo.jpg'),
                'type_code' => 'leo_copy',
            ])
            ->assertOk()
            ->assertJsonPath('reference_no', 'LEO/NS/1')
            ->assertJsonFragment([
                'remarks' => 'LEO date: 2025-04-10 · SB: SB-1 · Invoice: INV-1 · Port: Nhava Sheva · LEO granted',
            ]);
    }

    public function test_ocr_rejects_non_uploaded_type_on_desk(): void
    {
        config(['services.gemini.key' => 'test-key']);

        $this->actingAs($this->admin)
            ->postJson(route('export.ocr.extract'), [
                'file'      => UploadedFile::fake()->image('pack.jpg'),
                'type_code' => 'packing_list',
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

    public function test_phase1_lists_all_uploaded_types(): void
    {
        $this->assertSame(
            GeminiDocumentExtractor::UPLOADED_TYPES,
            GeminiDocumentExtractor::PHASE1_TYPES
        );
        $this->assertContains('clp', GeminiDocumentExtractor::PHASE1_TYPES);
        $this->assertContains('bl_final', GeminiDocumentExtractor::PHASE1_TYPES);
        $this->assertContains('ebrc', GeminiDocumentExtractor::PHASE1_TYPES);
        $this->assertNotContains('packing_list', GeminiDocumentExtractor::UPLOADED_TYPES);
    }
}
