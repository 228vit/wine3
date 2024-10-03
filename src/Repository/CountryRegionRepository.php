<?php

namespace App\Repository;

use App\Entity\CountryRegion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CountryRegion|null find($id, $lockMode = null, $lockVersion = null)
 * @method CountryRegion|null findOneBy(array $criteria, array $orderBy = null)
 * @method CountryRegion[]    findAll()
 * @method CountryRegion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CountryRegionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CountryRegion::class);
    }

    public function findWithWines()
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.products', 'product')
            ->getQuery()
            ->getResult()
            ;
    }


    // /**
    //  * @return CountryRegion[] Returns an array of CountryRegion objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CountryRegion
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
