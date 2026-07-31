<?php

namespace App\Services\Masters;

use App\Models\Buyer;
use Illuminate\Support\Facades\DB;

class BuyerService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Buyer
    {
        // One transaction: a buyer whose categories or carton marks failed to
        // write is a half-saved master the user would have to spot themselves.
        return DB::transaction(function () use ($data) {
            $buyer = Buyer::create($this->columns($data));

            $buyer->categories()->sync($data['category_ids'] ?? []);
            $this->syncCartonMarkings($buyer, $data['carton_markings'] ?? []);

            return $buyer;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Buyer $buyer, array $data): Buyer
    {
        return DB::transaction(function () use ($buyer, $data) {
            $buyer->update($this->columns($data));

            $buyer->categories()->sync($data['category_ids'] ?? []);
            $this->syncCartonMarkings($buyer, $data['carton_markings'] ?? []);

            return $buyer->refresh();
        });
    }

    /**
     * Nothing points at a buyer yet — orders, quotations and shipments come in
     * later phases. The check exists now so the guard is in one place when they
     * do, and so the controller's shape matches Category and Product.
     *
     * @return array{allowed: bool, reason: ?string}
     */
    public function canDelete(Buyer $buyer): array
    {
        return ['allowed' => true, 'reason' => null];
    }

    /**
     * Strip the two keys that are relations rather than columns, so the rest
     * can go through mass assignment and $fillable still governs it.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function columns(array $data): array
    {
        return array_diff_key($data, array_flip(['category_ids', 'carton_markings']));
    }

    /**
     * Rewrite a buyer's carton mark lines from the submitted rows.
     *
     * Deleted and re-inserted rather than diffed: `line_no` is the print order,
     * so reordering or removing a middle line renumbers everything after it,
     * and matching rows up by id to achieve the same result is more code for no
     * gain. Nothing references a marking row by id.
     *
     * Rows with no label AND no value are dropped — that is an empty line the
     * user left alone, not a line they want printed blank.
     *
     * @param  array<int, array{label?: ?string, value?: ?string}>  $rows
     */
    private function syncCartonMarkings(Buyer $buyer, array $rows): void
    {
        $buyer->cartonMarkings()->delete();

        $lineNo = 0;

        foreach ($rows as $row) {
            $label = trim((string) ($row['label'] ?? ''));
            $value = trim((string) ($row['value'] ?? ''));

            if ($label === '' && $value === '') {
                continue;
            }

            $buyer->cartonMarkings()->create([
                'line_no'     => ++$lineNo,
                'label'       => $label !== '' ? $label : 'LINE '.$lineNo,
                'value'       => $value !== '' ? $value : null,
                // The sheet stars the first three lines. Recorded so the packing
                // screen can later refuse to print a carton with a blank one.
                'is_required' => $lineNo <= 3,
            ]);
        }
    }
}
