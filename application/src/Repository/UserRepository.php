<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @param string|null $sortColumn
     * @param string|null $sortDirection
     * @return User[]
     */
    public function getAllSorted(?string $sortColumn='username', ?string $sortDirection='ASC'): array
    {
        $qb = $this->createQueryBuilder('u');
        if ($sortColumn !== null) {
            $qb->orderBy("u.$sortColumn", $sortDirection);
        }
        return $qb->getQuery()->getResult();
    }

    /**
     * @param string $apiKey
     * @return User|null
     */
    public function findByApiKey(string $apiKey): ?user
    {
        return $this->findOneBy(['apiKey' => $apiKey]);
    }

    /**
     * Due to a bug in this application, there can be multiple User records with the same
     * Discord ID. Find all of them, in creation order, in the hope that the newest one
     * is likely to be the one currently being used by the Discord user.
     *
     * @param string $discordId
     * @return User[]|array
     */
    public function findAllByDiscordId(string $discordId): array
    {
        $qb = $this->createQueryBuilder('u')
            ->andWhere('u.discordId = :discordId')
            ->setParameter('discordId', $discordId)
            ->orderBy('u.id', 'asc');
        return $qb->getQuery()->getResult();
    }

    /**
     * @param string $username
     * @return User|null
     */
    public function findByUsername(string $username): ?User
    {
        return $this->findOneBy(['username' => $username]);
    }
}
