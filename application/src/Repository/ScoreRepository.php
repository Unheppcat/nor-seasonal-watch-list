<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Repository;

use App\Entity\Score;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Score|null find($id, $lockMode = null, $lockVersion = null)
 * @method Score|null findOneBy(array $criteria, array $orderBy = null)
 * @method Score[]    findAll()
 * @method Score[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ScoreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Score::class);
    }

    public function findAllInRankOrder(): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.rankOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Score|null
     * @throws NonUniqueResultException
     */
    public function getDefaultScore(): ?Score
    {
        return $this->createQueryBuilder('s')
            ->where('s.slug = :defaultSlug')
            ->setParameter('defaultSlug', 'none')
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
    }
}
