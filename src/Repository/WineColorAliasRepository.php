<?php

namespace App\Repository;

use App\Entity\WineColorAlias;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method WineColorAlias|null find($id, $lockMode = null, $lockVersion = null)
 * @method WineColorAlias|null findOneBy(array $criteria, array $orderBy = null)
 * @method WineColorAlias[]    findAll()
 * @method WineColorAlias[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WineColorAliasRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WineColorAlias::class);
    }
    public function findLikeName(string $name)
    {
        return $this->createQueryBuilder('w')
            ->innerJoin('w.wineColor', 'wineColor')
            ->andWhere('w.name LIKE :val')
            ->setParameter('val', "%$name%")
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    // /**
    //  * @return WineColorAlias[] Returns an array of WineColorAlias objects
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
    public function findOneBySomeField($value): ?WineColorAlias
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
