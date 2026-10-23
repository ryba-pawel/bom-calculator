<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\BomItem;
use App\Entity\Material;
use App\Entity\Product;
use App\Enum\Unit;
use App\Service\CostCalculator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(CostCalculator::class)]
final class CostCalculatorTest extends TestCase
{
    private CostCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new CostCalculator();
    }

    public function testCalculatesMaterialsCostMarginAndFinalPrice(): void
    {
        $product = $this->createProduct('35.00', [
            ['14', '18.50'],
            ['6.5', '9.80'],
            ['24', '1.35'],
            ['5', '11.90'],
            ['2', '65.00'],
            ['2.5', '42.00'],
        ]);

        $breakdown = $this->calculator->calculate($product);

        self::assertCount(6, $breakdown->items);
        self::assertSame('649.60', $breakdown->materialsCost);
        self::assertSame('35.00', $breakdown->marginPercent);
        self::assertSame('227.36', $breakdown->marginAmount);
        self::assertSame('876.96', $breakdown->finalPrice);
        self::assertSame('PLN', $breakdown->currency);
    }

    public function testBreakdownContainsLineDetails(): void
    {
        $material = (new Material())
            ->setName('Lina polipropylenowa')
            ->setUnit(Unit::Meter)
            ->setUnitPrice('11.90');
        $product = (new Product())->setName('Huśtawka')->setMarginPercent('20.00');
        $product->addBomItem((new BomItem())->setMaterial($material)->setQuantity('5'));

        $line = $this->calculator->calculate($product)->items[0];

        self::assertSame('Lina polipropylenowa', $line->materialName);
        self::assertSame('m', $line->unit);
        self::assertSame('5', $line->quantity);
        self::assertSame('11.90', $line->unitPrice);
        self::assertSame('59.50', $line->totalCost);
    }

    #[DataProvider('lineCostRoundingProvider')]
    public function testRoundsLineCostHalfUpToTwoDecimals(string $quantity, string $unitPrice, string $expected): void
    {
        $product = $this->createProduct('0.00', [[$quantity, $unitPrice]]);

        $breakdown = $this->calculator->calculate($product);

        self::assertSame($expected, $breakdown->items[0]->totalCost);
        self::assertSame($expected, $breakdown->materialsCost);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function lineCostRoundingProvider(): iterable
    {
        yield 'dokładna kwota' => ['3', '0.10', '0.30'];
        yield 'połówka w górę' => ['2.5', '12.99', '32.48'];
        yield 'poniżej połówki w dół' => ['0.333', '1.00', '0.33'];
        yield 'ilość z 3 miejscami po przecinku' => ['1.005', '1.00', '1.01'];
    }

    #[DataProvider('marginRoundingProvider')]
    public function testRoundsMarginAmountToTwoDecimals(
        string $unitPrice,
        string $marginPercent,
        string $expectedMargin,
        string $expectedFinalPrice,
    ): void {
        $product = $this->createProduct($marginPercent, [['1', $unitPrice]]);

        $breakdown = $this->calculator->calculate($product);

        self::assertSame($expectedMargin, $breakdown->marginAmount);
        self::assertSame($expectedFinalPrice, $breakdown->finalPrice);
    }

    /**
     * @return iterable<string, array{string, string, string, string}>
     */
    public static function marginRoundingProvider(): iterable
    {
        yield 'marża 33.33%' => ['100.00', '33.33', '33.33', '133.33'];
        yield 'marża z zaokrągleniem w dół' => ['10.01', '12.50', '1.25', '11.26'];
        yield 'marża z zaokrągleniem w górę' => ['0.99', '50.00', '0.50', '1.49'];
        yield 'marża powyżej 100%' => ['80.00', '150.00', '120.00', '200.00'];
    }

    public function testZeroMarginKeepsFinalPriceEqualToMaterialsCost(): void
    {
        $product = $this->createProduct('0.00', [['2', '50.00']]);

        $breakdown = $this->calculator->calculate($product);

        self::assertSame('0.00', $breakdown->marginAmount);
        self::assertSame('100.00', $breakdown->finalPrice);
    }

    public function testProductWithoutBomCostsNothing(): void
    {
        $product = $this->createProduct('40.00', []);

        $breakdown = $this->calculator->calculate($product);

        self::assertSame([], $breakdown->items);
        self::assertSame('0.00', $breakdown->materialsCost);
        self::assertSame('0.00', $breakdown->marginAmount);
        self::assertSame('0.00', $breakdown->finalPrice);
    }

    public function testThrowsWhenBomItemIsIncomplete(): void
    {
        $product = (new Product())->setName('Wadliwy')->setMarginPercent('10.00');
        $product->addBomItem((new BomItem())->setQuantity('1'));

        $this->expectException(\LogicException::class);

        $this->calculator->calculate($product);
    }

    /**
     * @param list<array{string, string}> $bom lista par [ilość, cena jednostkowa]
     */
    private function createProduct(string $marginPercent, array $bom): Product
    {
        $product = (new Product())
            ->setName('Produkt testowy')
            ->setMarginPercent($marginPercent);

        foreach ($bom as $index => [$quantity, $unitPrice]) {
            $material = (new Material())
                ->setName(sprintf('Materiał %d', $index + 1))
                ->setUnit(Unit::Piece)
                ->setUnitPrice($unitPrice);

            $product->addBomItem((new BomItem())->setMaterial($material)->setQuantity($quantity));
        }

        return $product;
    }
}
