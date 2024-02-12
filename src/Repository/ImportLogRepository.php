<?php

namespace App\Repository;

use App\Entity\ImportLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ImportLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method ImportLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method ImportLog[]    findAll()
 * @method ImportLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ImportLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImportLog::class);
    }

    // /**
    //  * @return ImportLog[] Returns an array of ImportLog objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ImportLog
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
