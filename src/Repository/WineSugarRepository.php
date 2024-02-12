<?php

namespace App\Repository;

use App\Entity\WineSugar;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method WineSugar|null find($id, $lockMode = null, $lockVersion = null)
 * @method WineSugar|null findOneBy(array $criteria, array $orderBy = null)
 * @method WineSugar[]    findAll()
 * @method WineSugar[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WineSugarRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WineSugar::class);
    }

    public function getBaseQuery($alias = 's')
    {
        return $this->createQueryBuilder($alias);
    }

    public function getJoinedAliases()
    {
        $query = $this->getBaseQuery();

        return $query->leftJoin('s.aliases', 'aliases')
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
    //  * @return WineSugar[] Returns an array of WineSugar objects
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
    public function findOneBySomeField($value): ?WineSugar
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
