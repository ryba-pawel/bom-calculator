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

    /** "2.500" → "2,5", "14.000" → "14", "1500.000" → "1 500" (bez zbędnych zer z kolumny DECIMAL) */
    #[AsTwigFilter('decimal')]
    public function formatDecimal(string|int|float|null $value): string
    {
        $value = (string) $value;

        if (str_contains($value, '.')) {
            $value = rtrim(rtrim($value, '0'), '.');
        }

        [$integer, $fraction] = explode('.', $value, 2) + [1 => null];
        // Grupowanie tysięcy jak w filtrze "pln" i w kalkulatorze JS (useGrouping: 'always')
        $integer = (string) preg_replace('/\B(?=(\d{3})+(?!\d))/', self::NBSP, $integer);

        return null === $fraction ? $integer : $integer.','.$fraction;
    }
}
