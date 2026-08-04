<?php

namespace App\Services\Masters;

use App\Models\DocumentFormat;
use App\Models\DocumentFormatColumn;
use App\Models\DocumentFormatUnit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DocumentFormatService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): DocumentFormat
    {
        return DB::transaction(function () use ($data) {
            $format = DocumentFormat::create($this->columns($data));

            $this->syncUnits($format, $data['units'] ?? []);
            $this->syncColumns($format, $data);
            $this->syncImages($format, $data);

            return $format;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(DocumentFormat $format, array $data): DocumentFormat
    {
        return DB::transaction(function () use ($format, $data) {
            $format->update($this->columns($data));

            $this->syncUnits($format, $data['units'] ?? []);
            $this->syncColumns($format, $data);
            $this->syncImages($format, $data);

            return $format->refresh();
        });
    }

    /**
     * A format a category still offers cannot be deleted — category_format is
     * restrictOnDelete on the format side, so the database would reject it
     * anyway. Checked here so the user gets a sentence instead of a 500.
     *
     * @return array{allowed: bool, reason: ?string}
     */
    public function canDelete(DocumentFormat $format): array
    {
        $count = $format->categories()->count();

        if ($count > 0) {
            return [
                'allowed' => false,
                'reason'  => "This format is linked to {$count} category/categories. Remove it from them first.",
            ];
        }

        return ['allowed' => true, 'reason' => null];
    }

    /**
     * Delete a format and the images it owns.
     *
     * The rows go with the cascade, but the files on disk do not — those have
     * to be removed explicitly or the public disk grows forever. Done before
     * the delete: if the delete then fails, an orphaned file is a smaller
     * problem than a row pointing at a file that is gone.
     */
    public function delete(DocumentFormat $format): void
    {
        DB::transaction(function () use ($format) {
            foreach ($format->images as $image) {
                Storage::disk('public')->delete($image->path);
            }

            $format->images()->delete();
            $format->delete();
        });
    }

    /**
     * Strip the keys that are children rather than columns.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function columns(array $data): array
    {
        return array_diff_key($data, array_flip([
            'units', 'columns', 'custom_columns', 'images', 'keep_images',
        ]));
    }

    /**
     * Rewrite the unit list from the submitted chips.
     *
     * Deleted and re-inserted rather than diffed: sort_order is the order they
     * appear in, so removing a middle chip renumbers everything after it, and
     * nothing references a unit row by id. The request has already upper-cased
     * and de-duplicated them.
     *
     * @param  array<int, string>  $units
     */
    private function syncUnits(DocumentFormat $format, array $units): void
    {
        $format->units()->delete();

        foreach (array_values($units) as $index => $name) {
            $format->units()->create([
                'name'       => $name,
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * Rewrite the column list.
     *
     * Standard columns keep their key and their position in
     * DocumentFormatColumn::STANDARD, so the preview and the real item table
     * always draw them in the same order regardless of what the form posted.
     * Custom columns follow, in the order they were typed.
     *
     * A disabled standard column is still written, with is_enabled false — the
     * label the user typed against it survives being switched off and back on.
     *
     * @param  array<string, mixed>  $data
     */
    private function syncColumns(DocumentFormat $format, array $data): void
    {
        $format->columns()->delete();

        $posted = (array) ($data['columns'] ?? []);
        $order  = 0;

        foreach (DocumentFormatColumn::STANDARD as $key => $defaults) {
            $row = (array) ($posted[$key] ?? []);

            $format->columns()->create([
                'key'        => $key,
                'label'      => filled($row['label'] ?? null) ? trim($row['label']) : $defaults['label'],
                'is_enabled' => filled($row['enabled'] ?? null),
                'is_custom'  => false,
                // Print-only is a property of the column, not a per-format
                // choice, so the standard value wins over anything posted.
                'print_only' => $defaults['print_only'],
                'sort_order' => $order++,
            ]);
        }

        $used = array_keys(DocumentFormatColumn::STANDARD);

        foreach ((array) ($data['custom_columns'] ?? []) as $label) {
            $label = trim((string) $label);

            if ($label === '') {
                continue;
            }

            $key = $this->uniqueKey($label, $used);
            $used[] = $key;

            $format->columns()->create([
                'key'        => $key,
                'label'      => $label,
                'is_enabled' => true,
                'is_custom'  => true,
                'print_only' => false,
                'sort_order' => $order++,
            ]);
        }
    }

    /**
     * A slug for a custom column, guaranteed not to collide with a standard
     * key or with another custom column on the same format — the table has a
     * unique index on (format, key) and two columns both called "Remarks"
     * would otherwise fail the insert rather than the validation.
     *
     * @param  array<int, string>  $used
     */
    private function uniqueKey(string $label, array $used): string
    {
        $base = str($label)->slug('_')->limit(30, '')->toString();
        $base = $base !== '' ? $base : 'column';

        $key = $base;
        $n   = 2;

        while (in_array($key, $used, true)) {
            $key = "{$base}_{$n}";
            $n++;
        }

        return $key;
    }

    /**
     * Add newly uploaded images, drop the ones the user removed.
     *
     * Unlike units and columns this is a genuine diff, not a rewrite: the rows
     * point at files on disk, and deleting and re-inserting them would mean
     * re-uploading every image on every save.
     *
     * @param  array<string, mixed>  $data
     */
    private function syncImages(DocumentFormat $format, array $data): void
    {
        // Absent means "the form did not offer the field", not "remove
        // everything" — only treat it as a removal list when it was posted.
        if (array_key_exists('keep_images', $data)) {
            $keep = array_map('intval', (array) ($data['keep_images'] ?? []));

            foreach ($format->images()->whereNotIn('id', $keep ?: [0])->get() as $image) {
                Storage::disk('public')->delete($image->path);
                $image->delete();
            }
        }

        $order = (int) $format->images()->max('sort_order');

        foreach ((array) ($data['images'] ?? []) as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $format->images()->create([
                // store() generates the name, so a file called "../../x.png"
                // cannot decide where it lands.
                'path'          => $file->store('order-formats', 'public'),
                'original_name' => $file->getClientOriginalName(),
                'sort_order'    => ++$order,
            ]);
        }
    }

    /**
     * What a brand-new format starts with, so the create form is not a blank
     * grid the user has to fill in from nothing. Matches the prototype: every
     * standard column on, and the six default unit chips.
     *
     * @return array{columns: array<string, array{label: string, enabled: bool, print_only: bool}>, units: array<int, string>}
     */
    public function defaults(): array
    {
        $columns = [];

        foreach (DocumentFormatColumn::STANDARD as $key => $meta) {
            $columns[$key] = [
                'label'      => $meta['label'],
                'enabled'    => true,
                'print_only' => $meta['print_only'],
            ];
        }

        return ['columns' => $columns, 'units' => DocumentFormatUnit::DEFAULTS];
    }
}
