<?php

namespace App\Services\Masters;

use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class SupplierService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Supplier
    {
        // One transaction: a supplier whose categories or contacts failed to
        // write is a half-saved master the user would have to spot themselves.
        return DB::transaction(function () use ($data) {
            $supplier = Supplier::create($this->columns($data));

            $supplier->categories()->sync($data['category_ids'] ?? []);
            $this->syncContacts($supplier, $data);

            return $supplier;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Supplier $supplier, array $data): Supplier
    {
        return DB::transaction(function () use ($supplier, $data) {
            $supplier->update($this->columns($data));

            $supplier->categories()->sync($data['category_ids'] ?? []);
            $this->syncContacts($supplier, $data);

            return $supplier->refresh();
        });
    }

    /**
     * Nothing points at a supplier yet — purchase orders, bills and payments
     * come in later phases. The check exists now so the guard is in one place
     * when they do, and so the controller's shape matches Category, Product,
     * Agent and Buyer.
     *
     * @return array{allowed: bool, reason: ?string}
     */
    public function canDelete(Supplier $supplier): array
    {
        return ['allowed' => true, 'reason' => null];
    }

    /**
     * Strip the keys that are relations or sub-form fields rather than columns,
     * so the rest can go through mass assignment and $fillable still governs it.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function columns(array $data): array
    {
        return array_diff_key($data, array_flip([
            'category_ids',
            'contacts',
            'contact_name',
            'contact_designation_id',
            'contact_email',
            'contact_mobile',
        ]));
    }

    /**
     * Rewrite a supplier's contact list from the submitted form.
     *
     * Sheet cols H–K are the primary contact and col L is "other contact
     * persons"; both land in `supplier_contacts`, primary first. Deleted and
     * re-inserted rather than diffed — nothing references a contact row by id,
     * and matching rows up to achieve the same result is more code for no gain.
     * Same call as the buyer's carton markings.
     *
     * A row with no name is dropped. That is a line the user left alone, not a
     * person they meant to record — and the request has already rejected the
     * one case worth reporting, a row with a phone number but no name.
     *
     * @param  array<string, mixed>  $data
     */
    private function syncContacts(Supplier $supplier, array $data): void
    {
        $supplier->contacts()->delete();

        $rows = [];

        // Cols H–K. Written first so it is the primary row by construction,
        // which is why the table does not need a partial unique index to
        // enforce "one primary".
        if (filled($data['contact_name'] ?? null)) {
            $rows[] = [
                'name'           => trim((string) $data['contact_name']),
                'designation_id' => $data['contact_designation_id'] ?? null,
                'mobile'         => $data['contact_mobile'] ?? null,
                'email'          => $data['contact_email'] ?? null,
                'is_primary'     => true,
            ];
        }

        // Col L
        foreach ($data['contacts'] ?? [] as $row) {
            $name = trim((string) ($row['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $rows[] = [
                'name'           => $name,
                'designation_id' => $row['designation_id'] ?? null,
                'mobile'         => $row['mobile'] ?? null,
                'email'          => $row['email'] ?? null,
                // If cols H–K were left blank, the first extra person is the
                // one to reach — a supplier with contacts but no primary would
                // make every downstream "email the supplier" a special case.
                'is_primary'     => $rows === [],
            ];
        }

        foreach ($rows as $row) {
            $supplier->contacts()->create($row);
        }
    }
}
