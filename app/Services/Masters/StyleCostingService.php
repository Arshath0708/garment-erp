<?php

namespace App\Services\Masters;

use App\Models\GarmentStyle;
use App\Models\NumberSeries;
use App\Models\Product;
use App\Models\StyleCosting;
use App\Services\NumberSeriesService;
use App\Support\FinancialYear;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class StyleCostingService
{
    public function __construct(private readonly NumberSeriesService $numbers)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): StyleCosting
    {
        return DB::transaction(function () use ($data) {
            $style = GarmentStyle::query()->with('materials.product')->findOrFail($data['garment_style_id']);
            $financialYear = FinancialYear::current();
            $this->ensureNumberSeries($financialYear);

            $costing = new StyleCosting([
                'costing_date'     => $data['costing_date'] ?? now()->toDateString(),
                'garment_style_id' => $style->id,
                'buyer_id'         => $style->buyer_id,
                'cm_cost'          => (float) ($data['cm_cost'] ?? 0),
                'other_cost'       => (float) ($data['other_cost'] ?? 0),
                'status'           => 'draft',
                'notes'            => $data['notes'] ?? null,
            ]);
            $costing->financial_year = $financialYear;
            $costing->costing_num = $this->numbers->next('style-costing', $financialYear);
            $costing->save();

            $this->syncLines($costing, $this->resolveLines($style, $data['lines'] ?? []));

            return $costing->fresh('lines');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(StyleCosting $costing, array $data): StyleCosting
    {
        $this->assertDraft($costing);

        return DB::transaction(function () use ($costing, $data) {
            $style = GarmentStyle::query()->with('materials.product')->findOrFail($data['garment_style_id']);

            $costing->update([
                'costing_date'     => $data['costing_date'] ?? $costing->costing_date,
                'garment_style_id' => $style->id,
                'buyer_id'         => $style->buyer_id,
                'cm_cost'          => (float) ($data['cm_cost'] ?? 0),
                'other_cost'       => (float) ($data['other_cost'] ?? 0),
                'notes'            => $data['notes'] ?? null,
            ]);

            $this->syncLines($costing->fresh(), $this->resolveLines($style, $data['lines'] ?? []));

            return $costing->fresh('lines');
        });
    }

    public function approve(StyleCosting $costing): StyleCosting
    {
        if ($costing->isApproved()) {
            return $costing;
        }

        $costing->update([
            'status'      => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        return $costing->fresh();
    }

    public function delete(StyleCosting $costing): void
    {
        $this->assertDraft($costing);
        $costing->delete();
    }

    /**
     * BOM snapshot used on the create form before save.
     *
     * @return list<array<string, mixed>>
     */
    public function previewLines(GarmentStyle $style): array
    {
        $style->loadMissing('materials.product');

        return $this->linesFromStyle($style);
    }

    /**
     * @param  array<int, array<string, mixed>>  $posted
     * @return list<array<string, mixed>>
     */
    private function resolveLines(GarmentStyle $style, array $posted): array
    {
        $posted = array_values(array_filter($posted, function ($row) {
            return filled($row['product_id'] ?? null) || filled($row['description'] ?? null);
        }));

        return $posted !== [] ? $posted : $this->linesFromStyle($style);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function linesFromStyle(GarmentStyle $style): array
    {
        $lines = [];
        foreach ($style->materials as $material) {
            $product = $material->product;
            if (! $product) {
                continue;
            }

            $lines[] = [
                'product_id'  => $product->id,
                'description' => $product->name,
                'item_kind'   => $product->item_kind,
                'qty_per_pc'  => (float) $material->qty_per_pc,
                'unit'        => $material->unit ?: $product->unit_po,
                'rate'        => 0,
            ];
        }

        return $lines;
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    private function syncLines(StyleCosting $costing, array $lines): void
    {
        $costing->lines()->delete();

        $materialTotal = 0.0;
        $order = 0;
        foreach ($lines as $row) {
            $product = ! empty($row['product_id']) ? Product::query()->find($row['product_id']) : null;
            $qty = round((float) ($row['qty_per_pc'] ?? 0), 4);
            $rate = round((float) ($row['rate'] ?? 0), 4);
            $amount = round($qty * $rate, 4);
            $materialTotal += $amount;

            $costing->lines()->create([
                'product_id'  => $product?->id,
                'description' => $row['description'] ?? $product?->name ?? 'Material',
                'item_kind'   => $row['item_kind'] ?? $product?->item_kind,
                'qty_per_pc'  => $qty,
                'unit'        => $row['unit'] ?? $product?->unit_po,
                'rate'        => $rate,
                'amount'      => $amount,
                'sort_order'  => $order++,
            ]);
        }

        $cm = (float) $costing->cm_cost;
        $other = (float) $costing->other_cost;

        if ($order === 0 && $cm <= 0 && $other <= 0) {
            throw ValidationException::withMessages([
                'garment_style_id' => 'Add BOM materials on the style, or enter cut-make / other cost.',
            ]);
        }

        $costing->update([
            'material_cost'     => round($materialTotal, 4),
            'total_cost_per_pc' => round($materialTotal + $cm + $other, 4),
        ]);
    }

    private function assertDraft(StyleCosting $costing): void
    {
        if ($costing->isApproved()) {
            throw new RuntimeException('Approved costing cannot be changed. Make a new sheet if the cost has changed.');
        }
    }

    private function ensureNumberSeries(string $financialYear): void
    {
        NumberSeries::firstOrCreate(
            ['module' => 'style-costing', 'financial_year' => $financialYear],
            ['prefix' => 'CS/', 'padding' => 3, 'current_number' => 0, 'reset_yearly' => true]
        );
    }
}
