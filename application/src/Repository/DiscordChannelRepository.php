<?php

namespace App\Repository;

use App\Entity\DiscordChannel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DiscordChannel|null find($id, $lockMode = null, $lockVersion = null)
 * @method DiscordChannel|null findOneBy(array $criteria, array $orderBy = null)
 * @method DiscordChannel[]    findAll()
 * @method DiscordChannel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DiscordChannelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DiscordChannel::class);
    }

    // /**
    //  * @return DiscordChannel[] Returns an array of DiscordChannel objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?DiscordChannel
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
