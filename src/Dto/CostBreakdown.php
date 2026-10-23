<?php

declare(strict_types=1);

namespace App\Dto;

/**
 * Wynik kalkulacji kosztu produktu. Kwoty jako stringi z 2 miejscami po przecinku.
 */
final readonly class CostBreakdown
{
    /**
     * @param list<CostLine> $items
     */
    public function __construct(
        public ?int $productId,
        public string $productName,
        public array $items,
        public string $materialsCost,
        public string $marginPercent,
        public string $marginAmount,
        public string $finalPrice,
        public string $currency = 'PLN',
    ) {
    }
}
