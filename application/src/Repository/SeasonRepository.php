<?php /** @noinspection PhpUnused */

/** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Repository;

use App\Entity\Season;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

/**
 * @method Season|null find($id, $lockMode = null, $lockVersion = null)
 * @method Season|null findOneBy(array $criteria, array $orderBy = null)
 * @method Season[]    findAll()
 * @method Season[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SeasonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Season::class);
    }

    /**
     * @param bool $includeHidden
     * @return Season[]
     */
    public function getAllInRankOrder(bool $includeHidden = false): array
    {
        $qb = $this->createQueryBuilder('s')
            ->orderBy('s.rankOrder', 'asc');
        if (!$includeHidden) {
            $qb->andWhere('s.hiddenFromSeasonsList = :hidden')
                ->setParameter('hidden', false);
        }
        return $qb->getQuery()->getResult();
    }

    /**
     * @param DateTime|null $date
     * @return Season|null
     *
     * Get the default season to show for a given date.
     *
     * NOTE: this function now anticipates the coming season by one month. In
     * December, it selects the Winter quarter (of the next year), in March it
     * selects the Spring quarter, and so on.
     */
    public function getSeasonForDate(?DateTime $date = null): ?season
    {
        try {
            $date = $date ?? new DateTime();
            $year = (int)$date->format('Y');
            $month = (int)$date->format('m');
            switch ($month) {
                case 12:
                    $quarter = 'Winter';
                    ++$year;
                    break;
                case 1:
                case 2:
                    $quarter = 'Winter';
                    break;
                case 3:
                case 4:
                case 5:
                    $quarter = 'Spring';
                    break;
                case 6:
                case 7:
                case 8:
                    $quarter = 'Summer';
                    break;
                case 9:
                case 10:
                case 11:
                    $quarter = 'Fall';
                    break;
                default:
                    return null;
            }
            return $this->findOneBy(['year' => $year, 'yearPart' => $quarter]);
        } /** @noinspection PhpUnusedLocalVariableInspection */ catch (Exception $e) {
            return null;
        }
    }

    /**
     * @param bool $includeHidden
     * @return Season|null
     * @throws NonUniqueResultException
     */
    public function getFirstSeason(bool $includeHidden = false): ?season
    {
        $qb = $this->createQueryBuilder('s')
            ->orderBy('s.rankOrder', 'ASC')
            ->setMaxResults(1);
        if (!$includeHidden) {
            $qb->andWhere('s.hiddenFromSeasonsList = :hidden')
                ->setParameter('hidden', false);
        }
        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @return Season|null
     * @throws NonUniqueResultException
     */
    public function getMostRecentSeason(): ?season
    {
        $qb = $this->createQueryBuilder('s')
            ->orderBy('s.rankOrder', 'DESC')
            ->setMaxResults(1);
        $qb->andWhere('s.hiddenFromSeasonsList = :hidden')
            ->setParameter('hidden', false);
        return $qb->getQuery()->getOneOrNullResult();
    }
}
