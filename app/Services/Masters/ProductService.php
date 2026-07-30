<?php

namespace App\Services\Masters;

use App\Models\Product;
use App\Models\ProductIncentive;
use Illuminate\Support\Facades\DB;

class ProductService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $incentives = $data['incentives'] ?? [];
            unset($data['incentives']);

            $product = Product::create($data);
            $this->syncIncentives($product, $incentives);

            return $product;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $incentives = $data['incentives'] ?? [];
            unset($data['incentives']);

            $product->update($data);
            $this->syncIncentives($product, $incentives);

            return $product->refresh();
        });
    }

    /**
     * Write one row per incentive scheme that was actually filled in.
     *
     * A scheme with no percentage is not stored as a row of nulls — it is
     * deleted. Otherwise "does this product claim RoDTEP?" becomes "is there a
     * row, and is its percent non-null?" everywhere it is asked.
     *
     * @param  array<string, array<string, mixed>>  $incentives
     */
    private function syncIncentives(Product $product, array $incentives): void
    {
        foreach (array_keys(ProductIncentive::SCHEMES) as $scheme) {
            $row = $incentives[$scheme] ?? [];

            if (blank($row['percent_1'] ?? null)) {
                $product->incentives()->where('scheme', $scheme)->delete();

                continue;
            }

            $product->incentives()->updateOrCreate(
                ['scheme' => $scheme],
                [
                    'percent_1'            => $row['percent_1'],
                    'percent_2'            => $row['percent_2'] ?? null,
                    'cap_value'            => $row['cap_value'] ?? null,
                    'calculation_basis_id' => $row['calculation_basis_id'] ?? null,
                ]
            );
        }
    }

    /**
     * Nothing references products yet — POs, OCs and quotations arrive in later
     * phases. This method exists now so the guard has one home when they do,
     * rather than the check being added to the controller and then duplicated.
     *
     * @return array{allowed: bool, reason: ?string}
     */
    public function canDelete(Product $product): array
    {
        return ['allowed' => true, 'reason' => null];
    }
}
