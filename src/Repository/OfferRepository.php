<?php

namespace App\Repository;

use App\Entity\Offer;
use App\Entity\Supplier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\VarExporter\Internal\Hydrator;

/**
 * @method Offer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Offer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Offer[]    findAll()
 * @method Offer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OfferRepository extends ServiceEntityRepository
{
    private $cachedSlugs = [];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Offer::class);
    }

    public function getBaseQuery($alias = 'offer')
    {
        return $this->createQueryBuilder($alias);
    }

    public function slugExists(string $slug): bool
    {
        if (isset($this->cachedSlugs[$slug])) {
            return $this->cachedSlugs[$slug];
        }

        $res = $this->createQueryBuilder('offer')
            ->select('COUNT(offer.id)')
            ->where('offer.slug = :slug')
            ->setParameter('slug', $slug)
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();

        return 0 === (int)$res ? false : true;
    }

    public function getSupplierOffers(Supplier $supplier)
    {
        $result = $this->createQueryBuilder('offer')
            ->select('offer.id, offer.ymlId')
            ->where('offer.supplier = :supplier')
            ->andWhere('offer.ymlId IS NOT NULL')
            ->setParameter('supplier', $supplier)
            ->getQuery()
            ->getArrayResult()
        ;
        $resp = [];
        foreach ($result as $item) {
            $resp[$item['ymlId']] = $item['id'];
        }

        return $resp;
    }

    public function getByIds(array $ids)
    {
        $query = $this->getBaseQuery();
        return $query->andWhere('offer.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult()
        ;
    }
}
