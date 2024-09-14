<?php

namespace App\Repository;

use App\Entity\ProductGrapeSort;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProductGrapeSort|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductGrapeSort|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductGrapeSort[]    findAll()
 * @method ProductGrapeSort[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductGrapeSortRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductGrapeSort::class);
    }

    public function findAllByIds(array $ids)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult()
            ;
    }
    // /**
    //  * @return ProductGrapeSort[] Returns an array of ProductGrapeSort objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ProductGrapeSort
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
