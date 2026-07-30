<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * The list-screen filters every master needs: free-text search, status filter
 * and whitelisted sorting.
 *
 * A model says which columns are searchable and sortable; the scopes do the
 * rest. Blank input is a no-op rather than a filter matching nothing, so an
 * empty search box shows everything.
 */
trait Filterable
{
    /**
     * Columns scopeSearch() looks through. Override per model.
     *
     * @return array<int, string>
     */
    public function searchable(): array
    {
        return ['name'];
    }

    /**
     * Columns scopeSort() will accept. Anything else is ignored.
     *
     * Whitelisted rather than passed through, because `?sort=` lands straight
     * in an ORDER BY and the column list is attacker-controlled otherwise.
     *
     * @return array<int, string>
     */
    public function sortable(): array
    {
        return ['id', 'name', 'status', 'created_at'];
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $columns = $this->searchable();

        return $query->where(function (Builder $q) use ($columns, $term) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'like', "%{$term}%");
            }
        });
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        if (blank($status) || ! in_array($status, ['active', 'inactive'], true)) {
            return $query;
        }

        return $query->where('status', $status);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeSort(Builder $query, ?string $column, ?string $direction = 'asc'): Builder
    {
        if (! in_array($column, $this->sortable(), true)) {
            return $query->latest('id');
        }

        return $query->orderBy($column, $direction === 'desc' ? 'desc' : 'asc');
    }
}
