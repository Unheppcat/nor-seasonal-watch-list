<?php /** @noinspection PhpMultipleClassDeclarationsInspection */
/** @noinspection UnknownInspectionInspection */

/** @noinspection PhpUnused */

namespace App\Repository;

use App\Entity\Activity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Activity|null find($id, $lockMode = null, $lockVersion = null)
 * @method Activity|null findOneBy(array $criteria, array $orderBy = null)
 * @method Activity[]    findAll()
 * @method Activity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activity::class);
    }

    public function findAllInRankOrder(): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.rankOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Activity|null
     * @throws NonUniqueResultException
     */
    public function getDefaultActivity(): ?Activity
    {
        return $this->createQueryBuilder('a')
            ->where('a.slug = :defaultSlug')
            ->setParameter('defaultSlug', 'none')
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
    }
}
