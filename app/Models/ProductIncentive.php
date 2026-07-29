<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductIncentive extends Model
{
    /**
     * The three export incentive schemes on the Product Master sheet.
     * Adding a fourth is one entry here plus one enum value in the migration.
     */
    public const SCHEMES = [
        'drawback' => 'Drawback',
        'rosctl'   => 'RoSCTL',
        'rodtep'   => 'RoDTEP',
    ];

    /**
     * Only RoSCTL is quoted as two percentages (sheet cols O and P).
     * The form hides percent_2 for the other two.
     */
    public const TWO_PERCENT_SCHEMES = ['rosctl'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'scheme',
        'percent_1',
        'percent_2',
        'cap_value',
        'calculation_basis_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'percent_1' => 'decimal:3',
            'percent_2' => 'decimal:3',
            'cap_value' => 'decimal:4',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function calculationBasis(): BelongsTo
    {
        return $this->belongsTo(CalculationBasis::class);
    }

    public function schemeLabel(): string
    {
        return self::SCHEMES[$this->scheme] ?? $this->scheme;
    }
}
