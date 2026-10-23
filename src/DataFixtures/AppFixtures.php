<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\BomItem;
use App\Entity\Material;
use App\Entity\Product;
use App\Enum\Unit;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class AppFixtures extends Fixture
{
    /** @var array<string, array{string, Unit, string}> klucz => [nazwa, jednostka, cena jednostkowa] */
    private const MATERIALS = [
        'kantowka' => ['Kantówka sosnowa 90×90 mm', Unit::Meter, '18.50'],
        'deska' => ['Deska modrzewiowa 28×145 mm', Unit::Meter, '14.20'],
        'stal' => ['Profil stalowy ocynkowany 40×40 mm', Unit::Kilogram, '9.80'],
        'sruby' => ['Śruba zamkowa M10 z nakrętką', Unit::Piece, '1.35'],
        'lina' => ['Lina polipropylenowa Ø16 mm', Unit::Meter, '11.90'],
        'farba' => ['Farba ochronna do drewna', Unit::Liter, '42.00'],
        'siedzisko' => ['Siedzisko gumowe do huśtawki', Unit::Piece, '65.00'],
        'slizg' => ['Ślizg HDPE 2,2 m', Unit::Piece, '890.00'],
        'geowloknina' => ['Geowłóknina pod piasek', Unit::SquareMeter, '6.50'],
    ];

    /** @var list<array{string, string, array<string, string>}> [nazwa, marża %, [klucz materiału => ilość]] */
    private const PRODUCTS = [
        ['Huśtawka', '35.00', [
            'kantowka' => '14',
            'stal' => '6.5',
            'sruby' => '24',
            'lina' => '5',
            'siedzisko' => '2',
            'farba' => '2.5',
        ]],
        ['Zjeżdżalnia', '40.00', [
            'kantowka' => '18',
            'deska' => '22',
            'stal' => '12',
            'sruby' => '40',
            'slizg' => '1',
            'farba' => '4',
        ]],
        ['Piaskownica', '30.00', [
            'deska' => '16',
            'kantowka' => '2.4',
            'sruby' => '16',
            'geowloknina' => '4',
            'farba' => '1.5',
        ]],
    ];

    public function load(ObjectManager $manager): void
    {
        $materials = [];
        foreach (self::MATERIALS as $key => [$name, $unit, $unitPrice]) {
            $material = (new Material())
                ->setName($name)
                ->setUnit($unit)
                ->setUnitPrice($unitPrice);

            $manager->persist($material);
            $materials[$key] = $material;
        }

        foreach (self::PRODUCTS as [$name, $marginPercent, $bom]) {
            $product = (new Product())
                ->setName($name)
                ->setMarginPercent($marginPercent);

            foreach ($bom as $materialKey => $quantity) {
                $product->addBomItem(
                    (new BomItem())
                        ->setMaterial($materials[$materialKey])
                        ->setQuantity($quantity)
                );
            }

            $manager->persist($product);
        }

        $manager->flush();
    }
}
