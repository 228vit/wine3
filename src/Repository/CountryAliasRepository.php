<?php

namespace App\Repository;

use App\Entity\CountryAlias;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CountryAlias|null find($id, $lockMode = null, $lockVersion = null)
 * @method CountryAlias|null findOneBy(array $criteria, array $orderBy = null)
 * @method CountryAlias[]    findAll()
 * @method CountryAlias[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CountryAliasRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CountryAlias::class);
    }

    // /**
    //  * @return CountryAlias[] Returns an array of CountryAlias objects
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
    public function findOneBySomeField($value): ?CountryAlias
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
