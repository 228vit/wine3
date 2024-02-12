<?php

namespace App\Repository;

use App\Entity\WineSugarAlias;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method WineSugarAlias|null find($id, $lockMode = null, $lockVersion = null)
 * @method WineSugarAlias|null findOneBy(array $criteria, array $orderBy = null)
 * @method WineSugarAlias[]    findAll()
 * @method WineSugarAlias[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WineSugarAliasRepository extends ServiceEntityRepository
{
    private $cache = [];
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WineSugarAlias::class);
    }

    public function findOneByName($name): ?WineSugarAlias
    {
        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        $res = $this->createQueryBuilder('w')
            ->andWhere('w.name = :val')
            ->setParameter('val', $name)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (null !== $res) {
            $this->cache[$name] = $res;
        }

        return $res;
    }


    // /**
    //  * @return WineSugarAlias[] Returns an array of WineSugarAlias objects
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
}
