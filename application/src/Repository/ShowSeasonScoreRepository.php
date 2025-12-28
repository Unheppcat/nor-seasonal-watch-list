<?php /** @noinspection PhpMultipleClassDeclarationsInspection */
/** @noinspection UnknownInspectionInspection */
/** @noinspection PhpUnused */

namespace App\Repository;

use App\Entity\Season;
use App\Entity\Show;
use App\Entity\ShowSeasonScore;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use RuntimeException;

/**
 * @method ShowSeasonScore|null find($id, $lockMode = null, $lockVersion = null)
 * @method ShowSeasonScore|null findOneBy(array $criteria, array $orderBy = null)
 * @method ShowSeasonScore[]    findAll()
 * @method ShowSeasonScore[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ShowSeasonScoreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShowSeasonScore::class);
    }

    /**
     * @param Season $season
     * @param Show $show
     * @return QueryBuilder
     */
    private function findAllForSeasonAndShowQb(Season $season, Show $show): QueryBuilder
    {
        return $this->createQueryBuilder('s')
            ->where('s.season = :season')
            ->andWhere('s.show = :show')
            ->setParameter('season', $season)
            ->setParameter('show', $show);
    }

    /**
     * @param Season $season
     * @param Show $show
     * @param string $sortOrder
     * @param string $sortDirection
     * @return ShowSeasonScore[]
     */
    public function findAllForSeasonAndShow(
        Season $season,
        Show $show,
        string $sortOrder = 'username',
        string $sortDirection = 'ASC'
    ): array {
        $qb = $this->findAllForSeasonAndShowQb($season, $show)
            ->join('s.user', 'u');
        switch ($sortOrder) {
            case 'displayname':
                $qb->select('s, u, COALESCE(u.displayName, u.username) AS sortColumn')
                    ->orderBy('sortColumn', $sortDirection);
                break;
            case 'username':
                $qb->orderBy('u.username', $sortDirection);
                break;
            default:
                throw new RuntimeException('Unknown sort order requested.');
        }
        return $qb->getQuery()->getResult();
    }

    /**
     * @param Season $season
     * @param Show $show
     * @return ShowSeasonScore[]
     */
    public function getCountsForSeasonAndShow(Season $season, Show $show): array
    {
        return $this->findAllForSeasonAndShowQb($season, $show)
            ->select('count(s.score) as scoreCount, s.score as score, s.scoreName as scoreName')
            ->groupBy('s.score')
            ->addGroupBy('s.scoreName')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param User $user
     * @param Season $season
     * @return ShowSeasonScore[]
     */
    public function findAllForUserAndSeason(User $user, Season $season): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.user = :user')
            ->andWhere('s.season = :season')
            ->setParameter('user', $user)
            ->setParameter('season', $season)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param User $user
     * @return ShowSeasonScore[]
     */
    public function findAllForUser(User $user): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param User $user
     * @param Show $show
     * @param Season $season
     * @return ShowSeasonScore|null
     * @throws NonUniqueResultException
     */
    public function getForUserAndShowAndSeason(User $user, Show $show, Season $season): ?ShowSeasonScore
    {
        return $this->createQueryBuilder('s')
            ->where('s.user = :user')
            ->andWhere('s.show = :show')
            ->andWhere('s.season = :season')
            ->setParameter('user', $user)
            ->setParameter('show', $show)
            ->setParameter('season', $season)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param User $user
     * @param Season $season
     * @param string $showOrder
     * @param string $showDirection
     * @return ShowSeasonScore[]
     */
    public function getForUserAndSeason(
        User $user,
        Season $season,
        string $showOrder='romaji',
        string $showDirection='ASC'
    ): array {
        $orderColumn = ($showOrder === 'romaji' ? 'show.japaneseTitle' : 'show.englishTitle');
        $orderDirection = strtoupper($showDirection) === 'ASC' ? 'ASC' : 'DESC';
        return $this->createQueryBuilder('sss')
            ->join('sss.show', 'show')
            ->where('sss.user = :user')
            ->andWhere('sss.season = :season')
            ->setParameter('user', $user)
            ->setParameter('season', $season)
            ->orderBy($orderColumn, $orderDirection)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Season $season
     * @return array
     * @throws Exception
     */
    public function getActivitiesForSeason(Season $season): array
    {
        $sql = <<<EOF
SELECT
    ss.show_id,
    coalesce(ptw_j.my_count, 0) as ptw_count,
    coalesce(watching_j.my_count, 0) as watching_count,
    coalesce(stopped_j.my_count, 0) as stopped_count
FROM show_season ss
LEFT JOIN (
    SELECT
        sss3.show_id AS show_id,
        count(*) AS my_count
    FROM show_season_score sss3
    JOIN activity a3 on sss3.activity_id = a3.id AND a3.slug = 'ptw'
    WHERE sss3.season_id = :season_id
    GROUP BY sss3.show_id
) AS ptw_j ON ptw_j.show_id = ss.show_id
LEFT JOIN (
    SELECT
        sss4.show_id AS show_id,
        count(*) AS my_count
    FROM show_season_score sss4
    JOIN activity a4 on sss4.activity_id = a4.id AND a4.slug = 'watching'
    WHERE sss4.season_id = :season_id
    GROUP BY sss4.show_id
) AS watching_j ON watching_j.show_id = ss.show_id
LEFT JOIN (
    SELECT
        sss5.show_id AS show_id,
        count(*) AS my_count
    FROM show_season_score sss5
    JOIN activity a5 on sss5.activity_id = a5.id AND a5.slug = 'stopped'
    WHERE sss5.season_id = :season_id
    GROUP BY sss5.show_id
) AS stopped_j ON stopped_j.show_id = ss.show_id
WHERE ss.season_id = :season_id
;

EOF;
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        return $stmt->executeQuery(['season_id' => $season->getId()])->fetchAllAssociative();
    }

    /**
     * @param Season $season
     * @return array
     * @throws Exception
     */
    public function getScoresForSeason(Season $season): array
    {
        $sql = <<<EOF
SELECT
    ss.show_id,
    coalesce(yes_j.my_count, 0) as yes_count,
    coalesce(no_j.my_count, 0) as no_count,
    coalesce(unfavorable_j.my_count, 0) as unfavorable_count,
    coalesce(neutral_j.my_count, 0) as neutral_count,
    coalesce(favorable_j.my_count, 0) as favorable_count,
    coalesce(highly_favorable_j.my_count, 0) as highly_favorable_count,
    coalesce(th8a_j.my_count, 0) as th8a_count,
    coalesce(unfavorable_j.my_count, 0) +
        coalesce(neutral_j.my_count, 0) +
        coalesce(favorable_j.my_count, 0) +
        coalesce(highly_favorable_j.my_count, 0) +
        coalesce(th8a_j.my_count, 0) AS all_count,
    coalesce(score_j.my_total, 0) as score_total
FROM show_season ss
LEFT JOIN (
  SELECT 
      sss1.show_id AS show_id,
      count(*) AS my_count
  FROM show_season_score sss1
  JOIN score s1 ON sss1.score_id = s1.id AND s1.value >= 2
  WHERE sss1.season_id = :season_id
  GROUP BY sss1.show_id
) AS yes_j ON yes_j.show_id = ss.show_id
LEFT JOIN (
    SELECT
        sss2.show_id AS show_id,
        count(*) AS my_count
    FROM show_season_score sss2
    JOIN score s2 on sss2.score_id = s2.id AND s2.value < 0
    WHERE sss2.season_id = :season_id
    GROUP BY sss2.show_id
) AS no_j ON no_j.show_id = ss.show_id
LEFT JOIN (
    SELECT
        sss3.show_id AS show_id,
        count(*) AS my_count
    FROM show_season_score sss3
    JOIN score s3 on sss3.score_id = s3.id AND s3.slug = 'unfavorable'
    WHERE sss3.season_id = :season_id
    GROUP BY sss3.show_id
) AS unfavorable_j ON unfavorable_j.show_id = ss.show_id
LEFT JOIN (
    SELECT
        sss4.show_id AS show_id,
        count(*) AS my_count
    FROM show_season_score sss4
    JOIN score s4 on sss4.score_id = s4.id AND s4.slug = 'neutral'
    WHERE sss4.season_id = :season_id
    GROUP BY sss4.show_id
) AS neutral_j ON neutral_j.show_id = ss.show_id
LEFT JOIN (
    SELECT
        sss5.show_id AS show_id,
        count(*) AS my_count
    FROM show_season_score sss5
    JOIN score s5 on sss5.score_id = s5.id AND s5.slug = 'favorable'
    WHERE sss5.season_id = :season_id
    GROUP BY sss5.show_id
) AS favorable_j ON favorable_j.show_id = ss.show_id
LEFT JOIN (
    SELECT
        sss6.show_id AS show_id,
        count(*) AS my_count
    FROM show_season_score sss6
    JOIN score s6 on sss6.score_id = s6.id AND s6.slug = 'highly-favorable'
    WHERE sss6.season_id = :season_id
    GROUP BY sss6.show_id
) AS highly_favorable_j ON highly_favorable_j.show_id = ss.show_id
LEFT JOIN (
    SELECT
        sss7.show_id AS show_id,
        count(*) AS my_count
    FROM show_season_score sss7
    JOIN score s7 on sss7.score_id = s7.id AND s7.slug = 'th8a'
    WHERE sss7.season_id = :season_id
    GROUP BY sss7.show_id
) AS th8a_j ON th8a_j.show_id = ss.show_id
LEFT JOIN (
    SELECT
        sss9.show_id AS show_id,
        sum(s9.value) as my_total
    FROM show_season_score sss9
    JOIN score s9 on sss9.score_id = s9.id
    WHERE sss9.season_id = :season_id
    GROUP BY sss9.show_id
) AS score_j ON score_j.show_id = ss.show_id
WHERE ss.season_id = :season_id
;


EOF;
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        return $stmt->executeQuery(['season_id' => $season->getId()])->fetchAllAssociative();
    }
}
