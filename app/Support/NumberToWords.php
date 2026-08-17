<?php

namespace App\Support;

/**
 * "Amount Chargeable (in words)" on the E-Invoice — the Indian numbering
 * system (lakh/crore, not thousand/million), split at rupees and paise.
 */
class NumberToWords
{
    /**
     * @var array<int, string>
     */
    private const ONES = [
        '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
        'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen',
    ];

    /**
     * @var array<int, string>
     */
    private const TENS = [
        '', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety',
    ];

    public static function indian(float $amount): string
    {
        $rupees = (int) floor($amount);
        $paise = (int) round(($amount - $rupees) * 100);

        $words = self::rupeesToWords($rupees).' Rupees';

        if ($paise > 0) {
            $words .= ' and '.self::twoDigits($paise).' Paise';
        }

        return $words;
    }

    private static function rupeesToWords(int $number): string
    {
        if ($number === 0) {
            return 'Zero';
        }

        $crore = intdiv($number, 10000000);
        $number %= 10000000;
        $lakh = intdiv($number, 100000);
        $number %= 100000;
        $thousand = intdiv($number, 1000);
        $number %= 1000;
        $hundred = intdiv($number, 100);
        $remainder = $number % 100;

        $parts = [];

        if ($crore) {
            $parts[] = self::twoDigits($crore).' Crore';
        }
        if ($lakh) {
            $parts[] = self::twoDigits($lakh).' Lakh';
        }
        if ($thousand) {
            $parts[] = self::twoDigits($thousand).' Thousand';
        }
        if ($hundred) {
            $parts[] = self::ONES[$hundred].' Hundred';
        }
        if ($remainder) {
            $parts[] = self::twoDigits($remainder);
        }

        return implode(' ', $parts);
    }

    private static function twoDigits(int $n): string
    {
        if ($n < 20) {
            return self::ONES[$n];
        }

        $tens = intdiv($n, 10);
        $ones = $n % 10;

        return trim(self::TENS[$tens].' '.self::ONES[$ones]);
    }
}
