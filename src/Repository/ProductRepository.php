<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\Supplier;
use App\Entity\Vendor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    private $cachedSlugs = [];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function getBaseQuery($alias = 'p')
    {
        return $this->createQueryBuilder($alias);
    }

    public function noPicProductQry()
    {
        return $this->createQueryBuilder('product')
            ->andWhere('product.announcePic IS NULL')
            ->orderBy('product.name', 'ASC')
            ->getQuery()
        ;
    }

    public function getTopTen(int $limit = 10)
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    public function turnOffAll()
    {
        return $this->createQueryBuilder('p')
            ->update(Product::class, 'p')
            ->set('p.isActive', ':false')
            ->setParameter('false', false)
            ->getQuery()
            ->execute()
        ;
    }

    public function ajaxSearch(string $value)
    {
        return $this->createQueryBuilder('e')
            ->where('e.name LIKE :val')
            ->setParameter('val', "%$value%")
            ->orderBy('e.name', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByGrapeSorts(array $grapeSortIds)
    {
        return $this->createQueryBuilder('product')
            ->select('product')
            ->innerJoin('product.productGrapeSorts', 'productGrapeSorts')
            ->innerJoin('productGrapeSorts.grapeSort', 'grapeSort')
            ->where('grapeSort.id IN (:grapeSortIds)')
            ->setParameter('grapeSortIds', $grapeSortIds)
            ->getQuery()
            ->getResult()
        ;
    }

    public function getJoinedQuery($alias = 'product')
    {
        return $this->createQueryBuilder($alias)
            ->select($alias, 'country', 'region', 'vendor', 'foods', 'supplier', 'winecards', 'offers', 'productGrapeSorts', 'wineColor', 'wineSugar')
            ->innerJoin($alias.'.country', 'country')
            ->leftJoin($alias.'.region', 'region')
            ->leftJoin($alias.'.vendor', 'vendor')
            ->leftJoin($alias.'.foods', 'foods')
            ->leftJoin($alias.'.supplier', 'supplier')
            ->leftJoin($alias.'.winecards', 'winecards')
            ->leftJoin($alias.'.offers', 'offers')
            ->leftJoin($alias.'.productGrapeSorts', 'productGrapeSorts')
            ->leftJoin($alias.'.productRatings', 'productRatings')
            ->leftJoin($alias.'.wineColor', 'wineColor')
            ->leftJoin($alias.'.wineSugar', 'wineSugar')
        ;
    }

    public function getEditedDates()
    {
        return $this->createQueryBuilder('p')
            ->select('p.updatedAt')
            ->where('p.isEdited = true')
            ->addGroupBy('p.updatedAt')
            ->orderBy('p.updatedAt')
            ->getQuery()
            ->getResult()
        ;
    }

    public function countEditedByDate()
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id) as cnt, p.updatedAt, editor.id as editor_id, editor.email')
            ->innerJoin('p.editor', 'editor')
            ->where('p.isEdited = true')
            ->groupBy('p.editor')
            ->addGroupBy('p.updatedAt')
            ->getQuery()
            ->getResult()
        ;
    }

    public function slugExists(string $slug): bool
    {
        if (isset($this->cachedSlugs[$slug])) {
            return $this->cachedSlugs[$slug];
        }

        $res = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.slug = :slug')
            ->setParameter('slug', $slug)
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return 0 === (int)$res ? false : true;
    }

    public function searchProducts(array $searchParts, $limit = 20)
    {
        $qry = $this->createQueryBuilder('p');
        foreach ($searchParts as $index => $searchPart) {
            $qry->orWhere("p.name LIKE '{$searchPart}%'");
        }

        return $qry->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    public function getByIds(array $ids)
    {
        $query = $this->getBaseQuery();
        return $query->andWhere('p.id IN (:product_ids)')
            ->setParameter('product_ids', $ids)
            ->getQuery()
            ->getResult()
        ;
    }

    public function getJoinedOffers()
    {
        $query = $this->getBaseQuery();
        return $query->leftJoin('p.offers', 'offers')
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_ARRAY)
        ;
    }

    public function getAllWineColors()
    {
        return $this->getBaseQuery()
            ->select('p.color, COUNT(p.id) as cnt')
            ->groupBy('p.color')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getBottleVolumes(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.volume')
            ->where('p.volume > 0')
            ->groupBy('p.volume')
            ->addOrderBy('p.volume', 'ASC')
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_SCALAR)
        ;
    }

    public function getVendorBottleVolumes(Vendor $vendor): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.volume')
            ->where('p.volume > 0')
            ->andWhere('p.vendor = :vendor')
            ->setParameter('vendor', $vendor)
            ->groupBy('p.volume')
            ->addOrderBy('p.volume', 'ASC')
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_SCALAR)
        ;
    }

    public function getYears(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.year')
            ->where('p.year > 0')
            ->distinct()
            ->addOrderBy('p.year', 'ASC')
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_SCALAR)
        ;
    }

    public function getVendorYears(Vendor $vendor): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.year')
            ->where('p.year > 0')
            ->andWhere('p.vendor = :vendor')
            ->setParameter('vendor', $vendor)
            ->distinct()
            ->addOrderBy('p.year', 'ASC')
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_SCALAR)
        ;
    }

    public function getAlcohol(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.alcohol')
            ->where('p.alcohol > 0')
            ->distinct()
            ->addOrderBy('p.alcohol', 'ASC')
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_SCALAR)
        ;
    }

    public function findAllByYears(array $years)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.year IN (:years)')
            ->setParameter('years', $years)
            ->getQuery()
            ->getResult()
        ;
    }

    public function massDeleteRows(array $ids)
    {
        return $this->createQueryBuilder('e')
            ->delete()
            ->andWhere('e.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->execute()
        ;
    }

}
