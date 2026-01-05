<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Repository;

use App\Entity\Election;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Election|null find($id, $lockMode = null, $lockVersion = null)
 * @method Election|null findOneBy(array $criteria, array $orderBy = null)
 * @method Election[]    findAll()
 * @method Election[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ElectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Election::class);
    }

    /**
     * @param bool $includeRestricted
     * @return Election|null
     * @throws NonUniqueResultException
     */
    public function getFirstActiveElection(bool $includeRestricted = false): ?Election
    {
        static $firstActivityElection = null;
        static $storedIncludeRestricted = null;

        if ($firstActivityElection === null || $includeRestricted !== $storedIncludeRestricted) {
            $now = (new DateTime());
            $firstActivityElectionQ = $this->createQueryBuilder('e')
                ->where('e.startDate <= :now')
                ->andWhere('e.endDate >= :now')
                ->orderBy('e.startDate', 'ASC')
                ->setParameter('now', $now)
                ->setMaxResults(1);
            if (!$includeRestricted) {
                $firstActivityElectionQ->andWhere('e.restrictedAccess = FALSE OR e.restrictedAccess IS NULL');
            }
            $firstActivityElection = $firstActivityElectionQ->getQuery()->getOneOrNullResult();
            $storedIncludeRestricted = $includeRestricted;
        }

        return $firstActivityElection;
    }

    /**
     * @return Election|null
     * @throws NonUniqueResultException
     */
    public function getNextAvailableElection(): ?Election
    {
        $now = (new DateTime());
        return $this->createQueryBuilder('e')
            ->where('e.startDate > :now')
            ->orderBy('e.startDate', 'ASC')
            ->setParameter('now', $now)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get all currently active elections
     *
     * @return Election[]
     */
    public function getAllActiveElections(): array
    {
        $now = new DateTime();
        return $this->createQueryBuilder('e')
            ->where('e.startDate <= :now')
            ->andWhere('e.endDate >= :now')
            ->orderBy('e.startDate', 'ASC')
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }

    public function electionIsActive(bool $includeRestricted = false): bool
    {
        try {
            return ($this->getFirstActiveElection($includeRestricted) !== null);
        } /** @noinspection PhpUnusedLocalVariableInspection */ catch (NonUniqueResultException $e) {
            return true;
        }
    }

    /** @noinspection PhpUnused */
    public function electionIsAvailable(): bool
    {
        try {
            return ($this->getNextAvailableElection() !== null);
        } /** @noinspection PhpUnusedLocalVariableInspection */ catch (NonUniqueResultException $e) {
            return true;
        }
    }
}
