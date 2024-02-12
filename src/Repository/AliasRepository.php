<?php

namespace App\Repository;

use App\Entity\Alias;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Alias|null find($id, $lockMode = null, $lockVersion = null)
 * @method Alias|null findOneBy(array $criteria, array $orderBy = null)
 * @method Alias[]    findAll()
 * @method Alias[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AliasRepository extends ServiceEntityRepository
{
    private const WINE_COLOR = 'wine_color';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Alias::class);
    }
    public function getBaseQuery($alias = 'a')
    {
        return $this->createQueryBuilder($alias);
    }

    public function getWineColorAliases($wineColor)
    {
        return $this->getBaseQuery()
            ->select('a.alias')
            ->andWhere('a.modelName = :model')->setParameter('model', self::WINE_COLOR)
            ->andWhere('a.name = :name')->setParameter('name', $wineColor)
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_ARRAY)
        ;
    }


    // /**
    //  * @return Alias[] Returns an array of Alias objects
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
    public function findOneBySomeField($value): ?Alias
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
