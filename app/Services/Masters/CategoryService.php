<?php

namespace App\Services\Masters;

use App\Models\Category;
use App\Services\NumberSeriesService;
use Illuminate\Support\Facades\DB;

class CategoryService
{
    public function __construct(private readonly NumberSeriesService $numbers)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Category
    {
        return DB::transaction(function () use ($data) {
            // Validated request data goes through mass assignment, so $fillable
            // still governs what a user can set. The code is assigned as a
            // property instead — it is not fillable precisely so that a crafted
            // POST cannot supply one, which also means create() would drop it.
            $category = new Category($data);

            // Inside the transaction on purpose: next() holds a row lock on the
            // counter until this insert commits, so two people saving at the
            // same moment cannot both be handed CAT005.
            $category->code = $this->numbers->next('category');
            $category->save();

            return $category;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Category $category, array $data): Category
    {
        // The code is never updated. It appears on POs and export documents
        // that have already been sent.
        unset($data['code']);

        $category->update($data);

        return $category->refresh();
    }

    /**
     * A category with products or buyers behind it cannot be deleted — they
     * would be left pointing at nothing, and both FKs are restrictOnDelete so
     * the database would reject it anyway. Checked here so the user gets a
     * sentence instead of a 500.
     *
     * @return array{allowed: bool, reason: ?string}
     */
    public function canDelete(Category $category): array
    {
        $productCount = $category->products()->count();

        if ($productCount > 0) {
            return [
                'allowed' => false,
                'reason'  => "This category is used by {$productCount} product(s). Reassign or delete them first.",
            ];
        }

        // Buyer sheet col D links a buyer to any number of categories.
        $buyerCount = $category->buyers()->count();

        if ($buyerCount > 0) {
            return [
                'allowed' => false,
                'reason'  => "This category is used by {$buyerCount} buyer(s). Remove it from them first.",
            ];
        }

        return ['allowed' => true, 'reason' => null];
    }
}
