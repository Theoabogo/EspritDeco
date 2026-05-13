<?php

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

//    /**
//     * @return Product[] Returns an array of Product objects
//     */
    public function findAllWithImages(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.images', 'i')
            ->addSelect('i')
            ->getQuery()
            ->getResult();
    }

    public function findByFilters(
        string $query = '',
        ?int $categoryId = null,
        ?float $minPrice = null,
        ?float $maxPrice = null
    ): array {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.images', 'i')
            ->addSelect('i')
            ->leftJoin('p.category', 'c')
            ->addSelect('c');

        if ($query !== '') {
            $qb->andWhere('p.title LIKE :query OR p.description LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        if ($categoryId !== null) {
            $qb->andWhere('c.id = :categoryId')
               ->setParameter('categoryId', $categoryId);
        }

        if ($minPrice !== null) {
            $qb->andWhere('p.price >= :minPrice')
               ->setParameter('minPrice', $minPrice);
        }

        if ($maxPrice !== null) {
            $qb->andWhere('p.price <= :maxPrice')
               ->setParameter('maxPrice', $maxPrice);
        }

        return $qb->orderBy('p.id', 'DESC')->getQuery()->getResult();
    }

//    public function findOneBySomeField($value): ?Product
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
