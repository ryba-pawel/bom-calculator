<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * Produkt z pozycjami BOM i materiałami w jednym zapytaniu (bez N+1 przy liczeniu kosztów).
     */
    public function findWithBom(int $id): ?Product
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.bomItems', 'b')
            ->addSelect('b')
            ->leftJoin('b.material', 'm')
            ->addSelect('m')
            ->where('p.id = :id')
            ->setParameter('id', $id)
            ->orderBy('b.id', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
    }
}
