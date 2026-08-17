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

    public function test_ocr_requires_gemini_key(): void
    {
        config(['services.gemini.key' => null]);

        $checklist = $this->document->checklist->first(fn ($row) => $row->type->code === 'bl_final');

        $this->actingAs($this->admin)
            ->postJson(route('export.documents.checklist.ocr', [$this->document, $checklist]), [
                'file'      => UploadedFile::fake()->image('bl.jpg'),
                'type_code' => 'bl_final',
            ])
            ->assertStatus(503)
            ->assertJsonFragment(['message' => 'Gemini is not configured. Add GEMINI_API_KEY to your .env file.']);
    }

    public function test_ocr_extracts_bill_of_lading_fields(): void
    {
        config([
            'services.gemini.key'   => 'test-key',
            'services.gemini.model' => 'gemini-2.0-flash',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                'bl_number' => 'MAEU1234567',
                                'bl_date'   => '2026-08-10',
                            ]),
                        ]],
                    ],
                ]],
            ], 200),
        ]);

        $checklist = $this->document->checklist->first(fn ($row) => $row->type->code === 'bl_final');

        $this->actingAs($this->admin)
            ->postJson(route('export.documents.checklist.ocr', [$this->document, $checklist]), [
                'file'      => UploadedFile::fake()->image('bl.jpg'),
                'type_code' => 'bl_final',
            ])
            ->assertOk()
            ->assertJson([
                'reference_no' => 'MAEU1234567',
                'remarks'      => 'B/L date: 2026-08-10',
            ]);
    }

    public function test_ocr_extracts_leo_fields(): void
    {
        config([
            'services.gemini.key'   => 'test-key',
            'services.gemini.model' => 'gemini-2.0-flash',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                'leo_number' => 'LEO/2026/9988',
                                'leo_date'   => '2026-08-12',
                            ]),
                        ]],
                    ],
                ]],
            ], 200),
        ]);

        $checklist = $this->document->checklist->first(fn ($row) => $row->type->code === 'leo_copy');

        $this->actingAs($this->admin)
            ->postJson(route('export.documents.checklist.ocr', [$this->document, $checklist]), [
                'file'      => UploadedFile::fake()->create('leo.pdf', 80, 'application/pdf'),
                'type_code' => 'leo_copy',
            ])
            ->assertOk()
            ->assertJson([
                'reference_no' => 'LEO/2026/9988',
                'remarks'      => 'LEO date: 2026-08-12',
            ]);
    }

    public function test_ocr_rejects_unsupported_type(): void
    {
        config(['services.gemini.key' => 'test-key']);

        $checklist = $this->document->checklist->first(fn ($row) => $row->type->code === 'packing_list');

        $this->actingAs($this->admin)
            ->postJson(route('export.documents.checklist.ocr', [$this->document, $checklist]), [
                'file'      => UploadedFile::fake()->image('pack.jpg'),
                'type_code' => 'packing_list',
            ])
            ->assertStatus(422);
    }

    public function test_unsupported_types_list_is_documented(): void
    {
        $this->assertContains('bl_final', GeminiDocumentExtractor::SUPPORTED_TYPES);
        $this->assertContains('leo_copy', GeminiDocumentExtractor::SUPPORTED_TYPES);
        $this->assertNotContains('packing_list', GeminiDocumentExtractor::SUPPORTED_TYPES);
    }

    public function test_show_page_includes_extract_button_when_configured(): void
    {
        config(['services.gemini.key' => 'test-key']);

        $this->actingAs($this->admin)
            ->get(route('export.documents.show', $this->document))
            ->assertOk()
            ->assertSee('Extract with AI', false);
    }

    public function test_user_without_edit_permission_cannot_ocr(): void
    {
        config(['services.gemini.key' => 'test-key']);

        $viewer = User::factory()->create();
        $viewer->givePermissionTo('export-document.view');

        $checklist = $this->document->checklist->first(fn ($row) => $row->type->code === 'bl_final');

        $this->actingAs($viewer)
            ->postJson(route('export.documents.checklist.ocr', [$this->document, $checklist]), [
                'file'      => UploadedFile::fake()->image('bl.jpg'),
                'type_code' => 'bl_final',
            ])
            ->assertForbidden();
    }
}
