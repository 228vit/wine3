<?php

namespace App\Repository;

use App\Entity\Appellation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Appellation>
 *
 * @method Appellation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Appellation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Appellation[]    findAll()
 * @method Appellation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AppellationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Appellation::class);
    }

    public function allAsArray()
    {
        return $this->createQueryBuilder('appellation')
            ->select('appellation.id, appellation.name, region.id as r_id, region.name as r_name, country.name as c_name')
            ->innerJoin('appellation.country', 'country')
            ->innerJoin('appellation.countryRegion', 'region')
            ->orderBy('country.name', 'ASC')
            ->addOrderBy('region.name', 'ASC')
            ->addOrderBy('appellation.name', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;
    }


    public function asShortArray()
    {
        $rows = $this->createQueryBuilder('appellation')
            ->select('appellation.id, appellation.name, region.id as r_id, region.name as r_name, country.name as c_name')
            ->innerJoin('appellation.country', 'country')
            ->innerJoin('appellation.countryRegion', 'region')
            ->orderBy('country.name', 'ASC')
            ->addOrderBy('region.name', 'ASC')
            ->addOrderBy('appellation.name', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        $arr = [];
        foreach ($rows as $row) {
            $arr[$row['id']] = $row['name'];
        }

        return $arr;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Appellation $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Appellation $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return Appellation[] Returns an array of Appellation objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Appellation
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
