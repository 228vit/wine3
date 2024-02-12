<?php

namespace App\Repository;

use App\Entity\WineColor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method WineColor|null find($id, $lockMode = null, $lockVersion = null)
 * @method WineColor|null findOneBy(array $criteria, array $orderBy = null)
 * @method WineColor[]    findAll()
 * @method WineColor[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WineColorRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WineColor::class);
    }

    public function getAllJoined()
    {
        return $this->createQueryBuilder('w')
            ->leftJoin('w.aliases','aliases')
            ->getQuery()
            ->getResult()
        ;
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
    //  * @return WineColor[] Returns an array of WineColor objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('w.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?WineColor
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
