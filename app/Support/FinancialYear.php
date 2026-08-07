<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;

/**
 * The April–March financial year every export-trading document is numbered
 * and grouped against.
 *
 * Nothing in the app computed this before Inquiries needed it — NumberSeries
 * stores whatever financial_year string it's given, but nothing derived that
 * string from today's date. This is that one place.
 */
class FinancialYear
{
    /**
     * "2026-27" for any date from 2026-04-01 to 2027-03-31.
     */
    public static function current(): string
    {
        return self::forDate(Date::now());
    }

    public static function forDate(CarbonInterface $date): string
    {
        $startYear = $date->month >= 4 ? $date->year : $date->year - 1;

        return $startYear.'-'.substr((string) ($startYear + 1), -2);
    }
}
