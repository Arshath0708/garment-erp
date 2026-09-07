<?php

namespace Tests\Feature;

use App\Models\Buyer;
use App\Models\CompanyProfile;
use App\Models\ExportDocument;
use App\Models\GarmentStyle;
use App\Models\ProductionOrder;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Gst\GstInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ReportsAndGstTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();

        $this->seed(\Database\Seeders\PermissionsSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
        $this->seed(\Database\Seeders\SuperAdminSeeder::class);

        $this->admin = User::where('email', 'admin@garment.com')->firstOrFail();
    }

    public function test_guest_cannot_access_csv_reports(): void
    {
        $this->get(route('reports.open-pos.csv'))->assertRedirect(route('login'));
        $this->get(route('reports.open-exports.csv'))->assertRedirect(route('login'));
        $this->get(route('reports.open-capa.csv'))->assertRedirect(route('login'));
    }

    private function createDummyOc(int $buyerId): int
    {
        $catId = \Illuminate\Support\Facades\DB::table('categories')->insertGetId([
            'name'       => 'Knitwear',
            'code'       => 'KNT' . rand(100, 999),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $docFmtId = \Illuminate\Support\Facades\DB::table('document_formats')->insertGetId([
            'name'        => 'Standard Format',
            'description' => 'Standard',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
        $currId = \Illuminate\Support\Facades\DB::table('currencies')->insertGetId([
            'name'       => 'USD',
            'iso_code'   => 'USD' . rand(10, 99),
            'symbol'     => '$',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $incoId = \Illuminate\Support\Facades\DB::table('incoterms')->insertGetId([
            'name'       => 'FOB',
            'code'       => 'FOB' . rand(10, 99),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return \Illuminate\Support\Facades\DB::table('order_confirmations')->insertGetId([
            'oc_num'             => 'OC-' . rand(1000, 9999),
            'financial_year'     => '2026-2027',
            'oc_date'            => now(),
            'buyer_id'           => $buyerId,
            'category_id'        => $catId,
            'document_format_id' => $docFmtId,
            'currency_id'        => $currId,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
    }

    private function createExportDependencies(): array
    {
        $currId = \Illuminate\Support\Facades\DB::table('currencies')->insertGetId(['name' => 'USD', 'iso_code' => 'US' . rand(10, 99), 'symbol' => '$', 'created_at' => now(), 'updated_at' => now()]);
        $incoId = \Illuminate\Support\Facades\DB::table('incoterms')->insertGetId(['name' => 'FOB', 'code' => 'FOB' . rand(10, 99), 'created_at' => now(), 'updated_at' => now()]);
        $polId  = \Illuminate\Support\Facades\DB::table('ports')->insertGetId(['name' => 'Port A', 'code' => 'PTA' . rand(10, 99), 'type' => 'sea', 'created_at' => now(), 'updated_at' => now()]);
        $podId  = \Illuminate\Support\Facades\DB::table('ports')->insertGetId(['name' => 'Port B', 'code' => 'PTB' . rand(10, 99), 'type' => 'sea', 'created_at' => now(), 'updated_at' => now()]);
        $smId   = \Illuminate\Support\Facades\DB::table('shipment_methods')->insertGetId(['name' => 'Sea', 'created_at' => now(), 'updated_at' => now()]);

        return [$currId, $incoId, $polId, $podId, $smId];
    }

    public function test_admin_can_download_open_pos_csv(): void
    {
        $supplierId = \Illuminate\Support\Facades\DB::table('suppliers')->insertGetId([
            'company_name' => 'Fabric Supplier Ltd',
            'display_code' => 'SUP001',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $buyerId = \Illuminate\Support\Facades\DB::table('buyers')->insertGetId([
            'company_name' => 'UK Import Ltd',
            'display_code' => 'BUY001',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $ocId = $this->createDummyOc($buyerId);

        \Illuminate\Support\Facades\DB::table('purchase_orders')->insert([
            'po_num'                => 'PO-TEST-001',
            'financial_year'        => '2026-2027',
            'order_confirmation_id' => $ocId,
            'supplier_id'           => $supplierId,
            'status'                => 'draft',
            'po_date'               => now(),
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('reports.open-pos.csv'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('PO Number', $response->streamedContent());
        $this->assertStringContainsString('PO-TEST-001', $response->streamedContent());
        $this->assertStringContainsString('Fabric Supplier Ltd', $response->streamedContent());
    }

    public function test_admin_can_download_open_exports_csv(): void
    {
        $buyerId = \Illuminate\Support\Facades\DB::table('buyers')->insertGetId([
            'company_name' => 'UK Import Ltd',
            'display_code' => 'BUY001',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $ocId = $this->createDummyOc($buyerId);
        [$currId, $incoId, $polId, $podId, $smId] = $this->createExportDependencies();

        \Illuminate\Support\Facades\DB::table('export_documents')->insert([
            'doc_num'               => 'EXP-TEST-001',
            'financial_year'        => '2026-2027',
            'order_confirmation_id' => $ocId,
            'currency_id'           => $currId,
            'incoterm_id'           => $incoId,
            'port_of_loading_id'   => $polId,
            'port_of_discharge_id' => $podId,
            'shipment_method_id'   => $smId,
            'shipment_date'         => now(),
            'buyer_id'              => $buyerId,
            'status'                => 'draft',
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('reports.open-exports.csv'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Doc Number', $response->streamedContent());
        $this->assertStringContainsString('EXP-TEST-001', $response->streamedContent());
        $this->assertStringContainsString('UK Import Ltd', $response->streamedContent());
    }

    public function test_admin_can_download_open_capa_csv(): void
    {
        $styleId = \Illuminate\Support\Facades\DB::table('garment_styles')->insertGetId([
            'style_number' => 'ST-CAPA-01',
            'name'         => 'Basic Tee',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('production_orders')->insert([
            'order_number'      => 'PRD-CAPA-001',
            'garment_style_id'  => $styleId,
            'total_qty'         => 100,
            'qc_rejected_qty'   => 15,
            'status'            => 'In Progress',
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('reports.open-capa.csv'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Production Order Number', $response->streamedContent());
        $this->assertStringContainsString('PRD-CAPA-001', $response->streamedContent());
        $this->assertStringContainsString('ST-CAPA-01', $response->streamedContent());
    }

    public function test_gst_invoice_service_validates_unconfigured_credentials(): void
    {
        $service = new GstInvoiceService();
        $this->assertFalse($service->isConfigured());

        $buyerId = \Illuminate\Support\Facades\DB::table('buyers')->insertGetId([
            'company_name' => 'UK Import Ltd',
            'display_code' => 'BUY001',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $ocId = $this->createDummyOc($buyerId);
        [$currId, $incoId, $polId, $podId, $smId] = $this->createExportDependencies();

        $docId = \Illuminate\Support\Facades\DB::table('export_documents')->insertGetId([
            'doc_num'               => 'EXP-GST-001',
            'financial_year'        => '2026-2027',
            'order_confirmation_id' => $ocId,
            'currency_id'           => $currId,
            'incoterm_id'           => $incoId,
            'port_of_loading_id'   => $polId,
            'port_of_discharge_id' => $podId,
            'shipment_method_id'   => $smId,
            'shipment_date'         => now(),
            'buyer_id'              => $buyerId,
            'status'                => 'draft',
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        $doc = ExportDocument::findOrFail($docId);

        try {
            $service->generateIrn($doc);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('gsp_config', $e->errors());
        }
    }
}
