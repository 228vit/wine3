<?php

namespace App\Repository;

use App\Entity\Rating;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Rating|null find($id, $lockMode = null, $lockVersion = null)
 * @method Rating|null findOneBy(array $criteria, array $orderBy = null)
 * @method Rating[]    findAll()
 * @method Rating[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RatingRepository extends ServiceEntityRepository
{
    private $cache = [];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rating::class);
    }


    // todo: понять правильно ли в репе создавать запись
    public function findOrCreateByName(string $name,
                                       EntityManagerInterface $em): Rating
    {
        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        $row = $this->findOneBy(['name' => $name]);

        if (null === $row) {
            $row = (new Rating())->setName($name);

            $em->persist($row);
            $em->flush();
        }

        $this->cache[$row->getName()] = $row;

        return $row;
    }

    // /**
    //  * @return Rating[] Returns an array of Rating objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Rating
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
