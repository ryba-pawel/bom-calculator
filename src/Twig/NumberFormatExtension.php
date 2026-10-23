<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Attribute\AsTwigFilter;

/**
 * Formatowanie liczb w widokach w polskiej notacji (przecinek dziesiętny).
 */
final class NumberFormatExtension
{
    private const NBSP = "\u{00A0}";

    /** 1234.5 → "1 234,50 zł" */
    #[AsTwigFilter('pln')]
    public function formatPln(string|int|float|null $amount): string
    {
        return number_format((float) $amount, 2, ',', self::NBSP).self::NBSP.'zł';
    }

    /** "2.500" → "2,5", "14.000" → "14" (bez zbędnych zer z kolumny DECIMAL) */
    #[AsTwigFilter('decimal')]
    public function formatDecimal(string|int|float|null $value): string
    {
        $value = (string) $value;

        if (str_contains($value, '.')) {
            $value = rtrim(rtrim($value, '0'), '.');
        }

        return str_replace('.', ',', $value);
    }
}
