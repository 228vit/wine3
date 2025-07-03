<?php

namespace App\Repository;

use App\Entity\Supplier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Supplier|null find($id, $lockMode = null, $lockVersion = null)
 * @method Supplier|null findOneBy(array $criteria, array $orderBy = null)
 * @method Supplier[]    findAll()
 * @method Supplier[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SupplierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Supplier::class);
    }

    public function withCountOffers(): ?array
    {
        return $this->createQueryBuilder('s')
            ->select('s.id, s.name, COUNT(offer.id) AS totalOffers')
            ->leftJoin('s.offers', 'offer')
            ->groupBy('s.id')
            ->orderBy('s.id', 'ASC')
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_ARRAY)
        ;
    }

    public function getFirstSupplier(): ?Supplier
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }


    // /**
    //  * @return Supplier[] Returns an array of Supplier objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Supplier
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
