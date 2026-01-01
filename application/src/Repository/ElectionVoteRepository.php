<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

/** @noinspection UnknownInspectionInspection */

namespace App\Repository;

use App\Entity\Election;
use App\Entity\ElectionVote;
use App\Entity\Show;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ElectionVote|null find($id, $lockMode = null, $lockVersion = null)
 * @method ElectionVote|null findOneBy(array $criteria, array $orderBy = null)
 * @method ElectionVote[]    findAll()
 * @method ElectionVote[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ElectionVoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ElectionVote::class);
    }

    /**
     * @param User $user
     * @param Show $show
     * @param Election $election
     * @return ElectionVote|null
     * @throws NonUniqueResultException
     */
    public function getForUserAndShowAndElection(
        User $user,
        Show $show,
        Election $election
    ): ?ElectionVote {
        return $this->createQueryBuilder('ev')
            ->where('ev.user = :user')
            ->andWhere('ev.animeShow = :show')
            ->andWhere('ev.election = :election')
            ->setParameter('user', $user)
            ->setParameter('show', $show)
            ->setParameter('election', $election)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param User $user
     * @return ElectionVote[]|array
     */
    public function getAllForUser(User $user): array
    {
        return $this->createQueryBuilder('ev')
            ->where('ev.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Election $election
     * @return array
     * @throws Exception
     */
    public function getCountsForElection(
        Election $election
    ): array {
        $sql = <<<EOF
SELECT COUNT(ev.id) AS vote_count,
       esb.buff_rule as buff_rule,
       COUNT(ev.id) AS buffed_vote_count,
       ev.election_id AS election_id,
       ev.anime_show_id AS show_id,
       s.japanese_title AS japanese_title,
       s.english_title AS english_title,
       s.full_japanese_title AS full_japanese_title,
       s.anilist_id AS anilist_id
FROM election_vote ev
JOIN anime_show s ON s.id = ev.anime_show_id
LEFT JOIN election_show_buff esb ON esb.election_id = ev.election_id AND esb.anime_show_id = ev.anime_show_id
WHERE ev.chosen = 1
AND ev.election_id = :election_id
GROUP BY election_id, show_id, japanese_title, english_title, full_japanese_title, anilist_id, buff_rule
ORDER BY buffed_vote_count DESC
EOF;
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery(['election_id' => $election->getId()]);
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        if ($result !== null) {
            $rows = $result->fetchAllAssociative();
            foreach ($rows as $key => $row) {
                if ($row['buff_rule'] !== null) {
                    if (str_starts_with($row['buff_rule'], '*')) {
                        $originalCount = (int)$row['vote_count'];
                        $buff = substr($row['buff_rule'], 1);
                        $rows[$key]['buffed_vote_count'] = (int)round($originalCount * (float)$buff);
                    } elseif (str_starts_with($row['buff_rule'], '+')) {
                        $originalCount = (int)$row['vote_count'];
                        $buff = substr($row['buff_rule'], 1);
                        $rows[$key]['buffed_vote_count'] = (int)round($originalCount + (float)$buff);
                    }
                }
            }
            usort($rows, static function($a, $b) { return -($a['buffed_vote_count'] <=> $b['buffed_vote_count']); });
            return $rows;
        }
        return [];
    }

    /**
     * @param Election $election
     * @return array
     * @throws Exception
     */
    public function getRanksForElection(
        Election $election
    ): array {
        $sql = <<<EOF
SELECT ev.rank_choice AS rank_choice,
       ev.election_id AS election_id,
       ev.anime_show_id AS show_id,
       ev.user_id AS user_id,
       s.japanese_title AS japanese_title,
       s.english_title AS english_title,
       s.full_japanese_title AS full_japanese_title,
       s.anilist_id AS anilist_id
FROM election_vote ev
JOIN anime_show s ON s.id = ev.anime_show_id
WHERE ev.election_id = :election_id
ORDER BY user_id, show_id
EOF;
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery(['election_id' => $election->getId()]);
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        if ($result !== null) {
            return $result->fetchAllAssociative();
        }
        return [];
    }

    /**
     * @param User     $user
     * @param Election $election
     * @return int
     * @throws Exception
     */
    public function getCountForUserAndElection(User $user, Election $election): int
    {
        $sql = <<<EOF
SELECT COUNT(ev.id) AS user_vote_count
FROM election_vote ev
WHERE ev.chosen = 1
AND ev.election_id = :election_id
AND ev.user_id = :user_id
EOF;
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery(['user_id' => $user->getId(), 'election_id' => $election->getId()]);
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        return (int)$result?->fetchOne();
    }

    /**
     * @param Election $election
     * @return int
     * @throws Exception
     */
    public function getVoterCountForElection(
        Election $election
    ): int {
        $sql = match ($election->getElectionType()) {
            Election::SIMPLE_ELECTION => <<<EOF
SELECT COUNT(ev2.user_id) AS voter_count
FROM 
    (SELECT ev.user_id
    FROM election_vote ev
    WHERE ev.chosen = 1
    AND ev.election_id = :election_id
    GROUP BY ev.user_id) as ev2
EOF,
            Election::RANKED_CHOICE_ELECTION => <<<EOF
SELECT COUNT(ev2.user_id) AS voter_count
FROM 
    (SELECT ev.user_id
    FROM election_vote ev
    WHERE ev.rank_choice IS NOT NULL
    AND ev.election_id = :election_id
    GROUP BY ev.user_id) as ev2
EOF,
            default => throw new Exception('Unknown election type: ' . $election->getElectionType()),
        };
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery(['election_id' => $election->getId()]);
        return (int)$result->fetchOne();
    }

    /**
     * @param Election $election
     * @return int
     * @throws Exception
     */
    public function getBuffedVoteCountForElection(
        Election $election
    ): int {
        $counts = $this->getCountsForElection($election);
        return (int)array_reduce($counts, static function ($carry, $count) { return $carry + (int)$count['buffed_vote_count']; });
    }

    /**
     * @param Election $election
     * @return ElectionVote[]
     */
    public function getRawRankingVoteEntriesForElection(
        Election $election
    ): array {
        return $this->createQueryBuilder('ev')
            ->join('ev.user', 'user')
            ->join('ev.animeShow', 'animeShow')
            ->where('ev.election = :election')
            ->setParameter('election', $election)
            ->addOrderBy('user.id')
            ->addOrderBy('animeShow.englishTitle')
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if user has voted in a specific election
     *
     * For simple elections: User has voted if at least one vote has chosen = true
     * For ranked-choice elections: User has voted if ranks vary (not all the same default rank)
     *
     * @param User $user
     * @param Election $election
     * @return bool
     */
    public function hasUserVotedInElection(User $user, Election $election): bool
    {
        if ($election->getElectionType() === Election::SIMPLE_ELECTION) {
            // For simple elections: Check if any vote is chosen
            $result = $this->createQueryBuilder('ev')
                ->select('COUNT(ev.id)')
                ->where('ev.user = :user')
                ->andWhere('ev.election = :election')
                ->andWhere('ev.chosen = true')
                ->setParameter('user', $user)
                ->setParameter('election', $election)
                ->getQuery()
                ->getSingleScalarResult();

            return $result > 0;
        }

        if ($election->getElectionType() === Election::RANKED_CHOICE_ELECTION) {
            // For ranked-choice elections: Check if ranks vary (user made selections)
            // If MIN(rank) != MAX(rank), the user has assigned different ranks
            $result = $this->createQueryBuilder('ev')
                ->select('MIN(ev.rank) as minRank, MAX(ev.rank) as maxRank')
                ->where('ev.user = :user')
                ->andWhere('ev.election = :election')
                ->setParameter('user', $user)
                ->setParameter('election', $election)
                ->getQuery()
                ->getOneOrNullResult();

            // If no votes exist, or if all ranks are the same, user hasn't voted
            if ($result === null || $result['minRank'] === null) {
                return false;
            }

            return $result['minRank'] !== $result['maxRank'];
        }

        return false;
    }
}
