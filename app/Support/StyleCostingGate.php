<?php

namespace App\Support;

use App\Models\GarmentStyle;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class StyleCostingGate
{
    public static function assertApproved(GarmentStyle $style): void
    {
        if ($style->latestApprovedCosting()) {
            return;
        }

        throw ValidationException::withMessages([
            'garment_style_id' => "Approve a style costing for {$style->style_number} first. Work orders cannot be released and fabric POs cannot be raised without a signed cost.",
        ]);
    }

    public static function assertDesignApproved(?string $designNo): void
    {
        if (! filled($designNo)) {
            return;
        }

        $style = GarmentStyle::resolveFromDesignNo($designNo);
        if (! $style) {
            return;
        }

        self::assertApproved($style);
    }

    public static function raiseMessage(GarmentStyle $style): string
    {
        return "Approve a style costing for {$style->style_number} before raising a purchase order.";
    }

    public static function failRaise(GarmentStyle $style): never
    {
        throw new RuntimeException(self::raiseMessage($style));
    }
}
