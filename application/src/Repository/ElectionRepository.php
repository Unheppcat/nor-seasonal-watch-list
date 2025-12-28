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
     * @return Election|null
     * @throws NonUniqueResultException
     */
    public function getFirstActiveElection(): ?Election
    {
        static $firstActivityElection = null;

        if ($firstActivityElection === null) {
            $now = (new DateTime());
            $firstActivityElection = $this->createQueryBuilder('e')
                ->where('e.startDate <= :now')
                ->andWhere('e.endDate >= :now')
                ->orderBy('e.startDate', 'ASC')
                ->setParameter('now', $now)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
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

    public function electionIsActive(): bool
    {
        try {
            return ($this->getFirstActiveElection() !== null);
        } /** @noinspection PhpUnusedLocalVariableInspection */ catch (NonUniqueResultException $e) {
            return true;
        }
    }
}
