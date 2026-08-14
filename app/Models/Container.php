<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Container extends Model
{
    /**
     * @var array<string, string>
     */
    public const TYPES = [
        'lcl' => 'LCL',
        'fcl' => 'FCL',
    ];

    protected $fillable = [
        'container_no',
        'seal_no',
        'type',
        'remarks',
    ];

    public function exportDocuments(): BelongsToMany
    {
        return $this->belongsToMany(ExportDocument::class);
    }
}
