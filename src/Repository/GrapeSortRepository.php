<?php

namespace App\Repository;

use App\Entity\GrapeSort;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method GrapeSort|null find($id, $lockMode = null, $lockVersion = null)
 * @method GrapeSort|null findOneBy(array $criteria, array $orderBy = null)
 * @method GrapeSort[]    findAll()
 * @method GrapeSort[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GrapeSortRepository extends ServiceEntityRepository
{
    private $cache = [];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GrapeSort::class);
    }

    public function getQueryAllExceptMe(GrapeSort $grapeSort)
    {
        return $this->createQueryBuilder('g')
            ->where('g.id <> :my_id')
            ->setParameter('my_id', $grapeSort->getId())
            ->addOrderBy('g.name', 'ASC')
        ;
    }

    // todo: понять правильно ли в репе создавать запись
    public function findOrCreateByName(string $name,
                                        EntityManagerInterface $em): GrapeSort
    {
        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        $row = $this->findOneBy(['name' => $name]);

        if (null === $row) {
            $row = (new GrapeSort())->setName($name);

            $em->persist($row);
            $em->flush();
        }

        $this->cache[$row->getName()] = $row;

        return $row;
    }

    // /**
    //  * @return GrapeSort[] Returns an array of GrapeSort objects
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
    public function findOneBySomeField($value): ?GrapeSort
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
