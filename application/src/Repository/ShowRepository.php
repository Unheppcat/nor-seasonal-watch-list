<?php /** @noinspection PhpMultipleClassDeclarationsInspection */
/** @noinspection UnknownInspectionInspection */

/** @noinspection PhpUnused */

namespace App\Repository;

use App\Entity\Season;
use App\Entity\Show;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

/**
 * @method Show|null find($id, $lockMode = null, $lockVersion = null)
 * @method Show|null findOneBy(array $criteria, array $orderBy = null)
 * @method Show[]    findAll()
 * @method Show[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ShowRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Show::class);
    }

    /**
     * @param string|null $sortColumn
     * @param string|null $sortOrder
     * @return Show[]
     */
    public function getShowsSorted(
        ?string $sortColumn = 'romaji',
        ?string $sortOrder = 'ASC'
    ): array {
        $qb = $this->createQueryBuilder('s');
        $this->setOrderBy($qb, $sortColumn, $sortOrder);
        return $qb->getQuery()->getResult();
    }

    public function getShowsSortedPaged(
        ?string $sortColumn = 'romaji',
        ?string $sortOrder = 'ASC',
        ?int $pageNum = 1,
        ?int $perPage = 10,
        ?int $season = null
    ): Pagerfanta {
        $qb = $this->createQueryBuilder('s');
        $this->setOrderBy($qb, $sortColumn, $sortOrder);
        if ($season === -1) {
            $qb->leftJoin('s.seasons', 'seasons')
                ->andWhere('seasons.id IS NULL');
        } else if ($season > 0) {
            $qb->join('s.seasons', 'seasons')
                ->andWhere('seasons.id = :seasonId')
                ->setParameter('seasonId', $season);
        }
        $pagerfanta = new Pagerfanta(new QueryAdapter($qb));
        $pagerfanta->setMaxPerPage($perPage);
        $pagerfanta->setCurrentPage($pageNum);
        return $pagerfanta;
    }

    /**
     * @param Season $season
     * @param User|null $user
     * @param string|null $sortName
     * @return Show[]
     */
    public function getShowsForSeason(
        Season $season,
        ?User $user = null,
        ?string $sortName = 'show_asc'
    ): array {
        $qb = $this->getShowsForSeasonQb($season);
        $this->setOrderByName($qb, $season, $user, $sortName);
        return $qb->getQuery()->getResult();
    }

    /**
     * @param Season $season
     * @param string|null $sortColumn
     * @param string|null $sortOrder
     * @return Show[]
     */
    public function getShowsForSeasonWithNoChannel(
        Season $season,
        ?string $sortColumn = 'romaji',
        ?string $sortOrder = 'ASC'
    ): array {
        $qb = $this->getShowsForSeasonQb($season);
        $qb->leftJoin("s.discordChannel", 'discordChannel');
        $qb->andWhere('discordChannel IS NULL');
        $this->setOrderBy($qb, $sortColumn, $sortOrder);
        return $qb->getQuery()->getResult();
    }

    /**
     * @param Season $season
     * @param string|null $sortColumn
     * @param string|null $sortOrder
     * @return Show[]
     */
    public function getShowsForSeasonElectionEligible(
        Season $season,
        ?string $sortColumn = 'romaji',
        ?string $sortOrder = 'ASC'
    ): array {
        $qb = $this->getShowsForSeasonQb($season);
        $qb->andWhere('s.excludeFromElections IS NULL OR s.excludeFromElections = 0');
        $qb->andWhere('s.firstShow IS NULL');
        $qb->leftJoin('s.relatedShows', 'relatedShows')
            ->addSelect('relatedShows');
        $this->setOrderBy($qb, $sortColumn, $sortOrder);
        return $qb->getQuery()->getResult();
    }

    /**
     * @param Season $season
     * @return QueryBuilder
     */
    private function getShowsForSeasonQb(Season $season): QueryBuilder
    {
        return $this->createQueryBuilder('s')
            ->join('s.seasons', 'seasons')
            ->addSelect('seasons')
            ->where('seasons = :season')
            ->setParameter('season', $season);
    }

    /**
     * @param QueryBuilder $qb
     * @param string|null $sortColumn
     * @param string|null $sortOrder
     */
    private function setOrderBy(QueryBuilder $qb, ?string $sortColumn, ?string $sortOrder): void
    {
        $orderBy = match ($sortColumn) {
            'english' => 'englishTitle',
            'kanji' => 'fullJapaneseTitle',
            'excluded' => 'excludeFromElections',
            '', null, 'none' => null,
            default => 'japaneseTitle',
        };
        // Prevent invalid sort orders
        $sortOrder = (strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC');
        if ($orderBy !== null) {
            $qb->orderBy("s.$orderBy", $sortOrder);
        }
    }

    private function setOrderByName(
        QueryBuilder $qb,
        ?Season $season = null,
        ?User $user = null,
        ?string $sortName = ''
    ): void
    {
        switch ($sortName) {
            case 'show_asc':
                $orderBy = 's.japaneseTitle';
                $sortOrder = 'asc';
                break;
            case 'show_desc':
                $orderBy = 's.japaneseTitle';
                $sortOrder = 'desc';
                break;
            case 'statistics_highest':
            case 'recommendations_desc':
//                $this->addAvgScoreField($qb, $season);
//                $orderBy = 'avg_score';
                $this->addTotalScoreField($qb, $season);
                $orderBy = 'total_score';
                $sortOrder = 'desc';
                break;
            case 'statistics_lowest':
            case 'recommendations_asc':
//                $this->addAvgScoreField($qb, $season);
//                $orderBy = 'avg_score';
                $this->addTotalScoreField($qb, $season);
                $orderBy = 'total_score';
                $sortOrder = 'asc';
                break;
            case 'activity_highest':
                $this->addActivityRankField($qb, $season, $user);
                $orderBy = 'activity_rank';
                // 'Best' activity has the lowest rank value
                $sortOrder = 'asc';
                break;
            case 'activity_lowest':
                $this->addActivityRankField($qb, $season, $user);
                $orderBy = 'activity_rank';
                // 'Worst activity has the highest rank value
                $sortOrder = 'desc';
                break;
            case 'recommendation_highest':
                $this->addScoreRankField($qb, $season, $user);
                $orderBy = 'score_rank';
                // 'Best' score has the lowest rank value
                $sortOrder = 'asc';
                break;
            case 'recommendation_lowest':
                $this->addScoreRankField($qb, $season, $user);
                $orderBy = 'score_rank';
                // 'Worst' score has the highest rank value
                $sortOrder = 'desc';
                break;
            case 'activity_asc':
                $this->addActivityTotalField($qb, $season);
                $orderBy = 'total_activity';
                $sortOrder = 'asc';
                break;
            case 'activity_desc':
                $this->addActivityTotalField($qb, $season);
                $orderBy = 'total_activity';
                $sortOrder = 'desc';
                break;
            default:
                $orderBy = 's.japaneseTitle';
                $sortOrder = 'asc';
        }
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        if ($orderBy !== null) {
            $qb->orderBy($orderBy, $sortOrder);
            if ($orderBy !== 's.japaneseTitle') {
                $qb->addOrderBy('s.japaneseTitle', $sortOrder);
            }
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param Season|null $season
     * @param User|null $user
     */
    private function addScoreRankField(QueryBuilder $qb, ?Season $season, ?User $user): void
    {
        $qb->leftJoin('s.scores', 'scores');
        $qb->leftJoin('scores.score', 'score');
        if ($season !== null) {
            $qb->andWhere('scores.season = :season OR scores.season IS NULL')
                ->setParameter('season', $season);
        }
        if ($user !== null) {
            $qb->andWhere('scores.user = :user')
                ->setParameter('user', $user);
        }
        $qb->groupBy('s.id');
        $qb->select('s, min(score.rankOrder) AS score_rank');
    }

    /**
     * @param QueryBuilder $qb
     * @param Season|null $season
     * @param User|null $user
     */
    private function addActivityRankField(QueryBuilder $qb, ?Season $season, ?User $user): void
    {
        $qb->leftJoin('s.scores', 'scores');
        $qb->leftJoin('scores.activity', 'activity');
        if ($season !== null) {
            $qb->andWhere('scores.season = :season')
                ->setParameter('season', $season);
        }
        if ($user !== null) {
            $qb->andWhere('scores.user = :user')
                ->setParameter('user', $user);
        }
        $qb->groupBy('s.id');
        $qb->select('s, min(activity.rankOrder) AS activity_rank');
    }

    /**
     * @param QueryBuilder $qb
     * @param Season|null $season
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    private function addAvgScoreField(QueryBuilder $qb, ?Season $season): void
    {
        $qb->leftJoin('s.scores', 'scores');
        $qb->leftJoin('scores.score', 'score');
        if ($season !== null) {
            $qb->andWhere('scores.season = :season OR scores.season IS NULL')
                ->setParameter('season', $season);
        }
        $qb->groupBy('s.id');
        // Null rows are not included in the avg calculation.
        $qb->select('s, AVG(score.value) AS avg_score');
    }

    /**
     * @param QueryBuilder $qb
     * @param Season|null $season
     */
    private function addTotalScoreField(QueryBuilder $qb, ?Season $season): void
    {
        $qb->leftJoin('s.scores', 'scores');
        $qb->leftJoin('scores.score', 'score');
        if ($season !== null) {
            $qb->andWhere('scores.season = :season OR scores.season IS NULL')
                ->setParameter('season', $season);
        }
        $qb->groupBy('s.id');
        // Null rows are not included in the avg calculation.
        $qb->select('s, SUM(score.value) AS total_score');
    }

    private function addActivityTotalField(QueryBuilder $qb, ?Season $season): void
    {
        $qb->leftJoin('s.scores', 'scores');
        $qb->leftJoin('scores.activity', 'activity');
        if ($season !== null) {
            $qb->andWhere('scores.season = :season OR scores.season IS NULL')
                ->setParameter('season', $season);
        }
        $qb->groupBy('s.id');
        // Null rows are not included in the avg calculation.
        $qb->select('s, sum(activity.value) AS total_activity');
    }

    /**
     * @param Show $show
     * @return Show[]
     */
    public function getRelatedShows(Show $show): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.firstShow = :firstShow')
            ->setParameter('firstShow', $show)
            ->getQuery()->getResult();
    }

    /**
     * Search shows by title for autocomplete
     *
     * @param string $searchTerm
     * @param int|null $excludeId
     * @return Show[]
     */
    public function searchByTitle(string $searchTerm, ?int $excludeId = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.japaneseTitle LIKE :searchTerm')
            ->orWhere('s.englishTitle LIKE :searchTerm')
            ->orWhere('s.fullJapaneseTitle LIKE :searchTerm')
            ->orWhere('s.fullEnglishTitle LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->orderBy('s.japaneseTitle', 'ASC')
            ->setMaxResults(20);

        if ($excludeId !== null) {
            $qb->andWhere('s.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()->getResult();
    }
}
