<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\WineCard;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method WineCard|null find($id, $lockMode = null, $lockVersion = null)
 * @method WineCard|null findOneBy(array $criteria, array $orderBy = null)
 * @method WineCard[]    findAll()
 * @method WineCard[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WineCardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WineCard::class);
    }

    public function getAllByUser(UserInterface $user)
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.user = :user_id')
            ->setParameter('user_id', $user->getId())
            ->orderBy('w.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getWineColorsByUser(UserInterface $user)
    {
        return $this->createQueryBuilder('w')
            ->select('COUNT(wineColor.id) AS cnt, wineColor.name')
            ->innerJoin('w.products', 'products')
            ->innerJoin('products.wineColor', 'wineColor')
            ->andWhere('w.user = :user_id')
            ->setParameter('user_id', $user->getId())
            ->groupBy('wineColor.id')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getWinePricesByUser(UserInterface $user)
    {
        return $this->createQueryBuilder('w')
            ->select('COUNT(product.id) AS cnt, ROUND(product.price / 1000, 0) AS name')
            ->innerJoin('w.products', 'product')
            ->andWhere('w.user = :user_id')
            ->setParameter('user_id', $user->getId())
            ->groupBy('name')
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_ARRAY)
        ;
    }

    public function getWineSugarsByUser(UserInterface $user)
    {
        return $this->createQueryBuilder('w')
            ->select('COUNT(wineSugar.id) AS cnt, wineSugar.name')
            ->innerJoin('w.products', 'product')
            ->innerJoin('product.wineSugar', 'wineSugar')
            ->andWhere('w.user = :user_id')
            ->setParameter('user_id', $user->getId())
            ->groupBy('wineSugar.id')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getGrapeSortsByUser(UserInterface $user)
    {
        return $this->createQueryBuilder('w')
            ->select('COUNT(grapeSort.id) AS cnt, grapeSort.name')
            ->innerJoin('w.products', 'products')
            ->innerJoin('products.productGrapeSorts', 'productGrapeSorts')
            ->innerJoin('productGrapeSorts.grapeSort', 'grapeSort')
            ->andWhere('w.user = :user_id')
            ->setParameter('user_id', $user->getId())
            ->groupBy('grapeSort.id')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getWineCountriesByUser(UserInterface $user)
    {
        return $this->createQueryBuilder('w')
            ->select('COUNT(country.id) AS cnt, country.name')
            ->innerJoin('w.products', 'products')
            ->innerJoin('products.country', 'country')
            ->andWhere('w.user = :user_id')
            ->setParameter('user_id', $user->getId())
            ->groupBy('country.id')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getSuppliersByUser(UserInterface $user)
    {
        return $this->createQueryBuilder('w')
            ->select('COUNT(supplier.id) AS cnt, supplier.name')
            ->innerJoin('w.products', 'products')
            ->innerJoin('products.supplier', 'supplier')
            ->andWhere('w.user = :user_id')
            ->setParameter('user_id', $user->getId())
            ->groupBy('supplier.id')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getVolumesByUser(UserInterface $user, $countZeroVolume = false)
    {
        $qry = $this->createQueryBuilder('w')
            ->select('DISTINCT(p.volume) as name, COUNT(p.id) AS cnt')
            ->innerJoin('w.products', 'p')
            ->innerJoin('w.user', 'u')
            ->andWhere('w.user = :user_id')
            ->setParameter('user_id', $user->getId())
            ->groupBy('p.volume')
        ;

        if (false === $countZeroVolume) {
            $qry->andWhere('p.volume > 0');
        }

        return $qry
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_ARRAY)
        ;
    }

    public function countMyWineCards(User $user): ?int
    {
        return $this->createQueryBuilder('o')
            ->select('COUNT(o.id) as cnt')
            ->andWhere('o.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    // /**
    //  * @return WineCard[] Returns an array of WineCard objects
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
    public function findOneBySomeField($value): ?WineCard
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
