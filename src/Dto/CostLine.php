<?php

declare(strict_types=1);

namespace App\Dto;

/**
 * Jedna pozycja rozbicia kosztów: materiał, ilość i jej koszt.
 */
final readonly class CostLine
{
    public function __construct(
        public ?int $bomItemId,
        public ?int $materialId,
        public string $materialName,
        public string $unit,
        public string $quantity,
        public string $unitPrice,
        public string $totalCost,
    ) {
    }
}
