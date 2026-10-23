<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CostBreakdown;
use App\Dto\CostLine;
use App\Entity\Product;

/**
 * Liczy koszt produktu na podstawie jego BOM.
 *
 * Kwoty są stringami liczonymi przez bcmath, żeby uniknąć błędów zaokrągleń float.
 * Koszt każdej pozycji jest zaokrąglany do groszy (jak na fakturze),
 * a marża liczona od sumy kosztów materiałów.
 */
final class CostCalculator
{
    private const MONEY_SCALE = 2;
    private const CALCULATION_SCALE = 6;

    public function calculate(Product $product): CostBreakdown
    {
        $items = [];
        $materialsCost = '0.00';

        foreach ($product->getBomItems() as $bomItem) {
            $material = $bomItem->getMaterial();
            $quantity = $bomItem->getQuantity();
            $unitPrice = $material?->getUnitPrice();

            if (null === $material || null === $quantity || null === $unitPrice) {
                throw new \LogicException('Pozycja BOM musi mieć materiał, ilość i cenę jednostkową.');
            }

            $lineCost = $this->roundMoney(bcmul($quantity, $unitPrice, self::CALCULATION_SCALE));
            $materialsCost = bcadd($materialsCost, $lineCost, self::MONEY_SCALE);

            $items[] = new CostLine(
                materialId: $material->getId(),
                materialName: (string) $material->getName(),
                unit: $material->getUnit()?->value ?? '',
                quantity: $quantity,
                unitPrice: $unitPrice,
                totalCost: $lineCost,
            );
        }

        $marginPercent = $product->getMarginPercent() ?? '0.00';
        $marginAmount = $this->roundMoney(
            bcdiv(bcmul($materialsCost, $marginPercent, self::CALCULATION_SCALE), '100', self::CALCULATION_SCALE)
        );

        return new CostBreakdown(
            productId: $product->getId(),
            productName: (string) $product->getName(),
            items: $items,
            materialsCost: $materialsCost,
            marginPercent: $marginPercent,
            marginAmount: $marginAmount,
            finalPrice: bcadd($materialsCost, $marginAmount, self::MONEY_SCALE),
        );
    }

    /**
     * Zaokrąglenie do groszy "połówka w górę". bcmath tylko obcina cyfry, stąd +0.005
     * przed obcięciem; poprawne dla kwot nieujemnych, co gwarantuje walidacja encji.
     */
    private function roundMoney(string $amount): string
    {
        return bcadd($amount, '0.005', self::MONEY_SCALE);
    }
}
