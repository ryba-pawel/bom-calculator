<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\BomItem;
use App\Entity\Material;
use App\Entity\Product;
use App\Enum\Unit;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Testy na bazie testowej MySQL; DAMA DoctrineTestBundle wycofuje zmiany po każdym teście.
 */
final class ProductApiTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testCostEndpointReturnsCostBreakdown(): void
    {
        $product = $this->createProduct('Huśtawka testowa', '35.00', [
            ['Kantówka sosnowa', Unit::Meter, '18.50', '14'],
            ['Farba ochronna', Unit::Liter, '42.00', '2.5'],
            ['Śruba M10', Unit::Piece, '1.35', '24'],
        ]);

        $this->client->request('GET', sprintf('/api/products/%d/cost', $product->getId()));

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/json');

        $data = $this->decodeResponse();
        self::assertSame($product->getId(), $data['productId']);
        self::assertSame('Huśtawka testowa', $data['productName']);
        self::assertSame('396.40', $data['materialsCost']);
        self::assertSame('35.00', $data['marginPercent']);
        self::assertSame('138.74', $data['marginAmount']);
        self::assertSame('535.14', $data['finalPrice']);
        self::assertSame('PLN', $data['currency']);

        self::assertCount(3, $data['items']);
        self::assertSame('Farba ochronna', $data['items'][1]['materialName']);
        self::assertSame('l', $data['items'][1]['unit']);
        self::assertSame('2.500', $data['items'][1]['quantity']);
        self::assertSame('42.00', $data['items'][1]['unitPrice']);
        self::assertSame('105.00', $data['items'][1]['totalCost']);
    }

    public function testCostEndpointReturnsZeroCostsForProductWithoutBom(): void
    {
        $product = $this->createProduct('Nowy produkt', '20.00', []);

        $this->client->request('GET', sprintf('/api/products/%d/cost', $product->getId()));

        self::assertResponseIsSuccessful();
        $data = $this->decodeResponse();
        self::assertSame([], $data['items']);
        self::assertSame('0.00', $data['materialsCost']);
        self::assertSame('0.00', $data['finalPrice']);
    }

    public function testCostEndpointReturns404ForUnknownProduct(): void
    {
        $this->client->request('GET', '/api/products/999999/cost');

        self::assertResponseStatusCodeSame(404);
        self::assertResponseHeaderSame('Content-Type', 'application/json');
        self::assertSame(['error' => 'Produkt o id 999999 nie istnieje.'], $this->decodeResponse());
    }

    public function testCostEndpointReturnsJson404ForNonNumericId(): void
    {
        $this->client->request('GET', '/api/products/abc/cost');

        self::assertResponseStatusCodeSame(404);
        self::assertResponseHeaderSame('Content-Type', 'application/json');
        self::assertSame(['error' => 'Not Found'], $this->decodeResponse());
    }

    public function testCostEndpointReturnsJson404ForIdOverflowingInt(): void
    {
        $this->client->request('GET', '/api/products/99999999999999999999/cost');

        self::assertResponseStatusCodeSame(404);
        self::assertResponseHeaderSame('Content-Type', 'application/json');
        self::assertSame(['error' => 'Not Found'], $this->decodeResponse());
    }

    public function testCostEndpointRejectsPost(): void
    {
        $product = $this->createProduct('Piaskownica', '30.00', []);

        $this->client->request('POST', sprintf('/api/products/%d/cost', $product->getId()));

        self::assertResponseStatusCodeSame(405);
        self::assertResponseHeaderSame('Allow', 'GET');
    }

    public function testListEndpointReturnsProductsSortedByName(): void
    {
        $this->createProduct('Zjeżdżalnia', '40.00', []);
        $this->createProduct('Huśtawka', '35.00', []);

        $this->client->request('GET', '/api/products');

        self::assertResponseIsSuccessful();
        $data = $this->decodeResponse();
        self::assertSame(['Huśtawka', 'Zjeżdżalnia'], array_column($data, 'name'));
        self::assertSame(['id', 'name', 'marginPercent'], array_keys($data[0]));
        self::assertSame('35.00', $data[0]['marginPercent']);
    }

    /**
     * @param list<array{string, Unit, string, string}> $bom lista [nazwa materiału, jednostka, cena, ilość]
     */
    private function createProduct(string $name, string $marginPercent, array $bom): Product
    {
        $product = (new Product())->setName($name)->setMarginPercent($marginPercent);

        foreach ($bom as [$materialName, $unit, $unitPrice, $quantity]) {
            $material = (new Material())->setName($materialName)->setUnit($unit)->setUnitPrice($unitPrice);
            $this->entityManager->persist($material);

            $product->addBomItem((new BomItem())->setMaterial($material)->setQuantity($quantity));
        }

        $this->entityManager->persist($product);
        $this->entityManager->flush();
        // Czyścimy identity map, żeby API czytało dane z bazy, a nie z pamięci
        $this->entityManager->clear();

        return $product;
    }

    /**
     * @return array<mixed>
     */
    private function decodeResponse(): array
    {
        return json_decode((string) $this->client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
    }
}
