<?php

namespace App\Support;

class WhatsappPhone
{
    public static function digits(?string $raw, string $countryCode = '91'): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $raw) ?: '';
        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 10) {
            $digits = $countryCode.$digits;
        }

        if (strlen($digits) < 10 || strlen($digits) > 15) {
            return null;
        }

        return $digits;
    }

    public static function chatUrl(string $digits, string $text): string
    {
        return 'https://wa.me/'.$digits.'?text='.rawurlencode($text);
    }
}
