<?php

namespace App\Repository;

use App\Entity\GrapeSortAlias;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method GrapeSortAlias|null find($id, $lockMode = null, $lockVersion = null)
 * @method GrapeSortAlias|null findOneBy(array $criteria, array $orderBy = null)
 * @method GrapeSortAlias[]    findAll()
 * @method GrapeSortAlias[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GrapeSortAliasRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GrapeSortAlias::class);
    }

    // /**
    //  * @return GrapeSortAlias[] Returns an array of GrapeSortAlias objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('g.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?GrapeSortAlias
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
