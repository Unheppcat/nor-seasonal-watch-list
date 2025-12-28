<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Repository;

use App\Entity\ElectionShowBuff;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ElectionShowBuff|null find($id, $lockMode = null, $lockVersion = null)
 * @method ElectionShowBuff|null findOneBy(array $criteria, array $orderBy = null)
 * @method ElectionShowBuff[]    findAll()
 * @method ElectionShowBuff[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ElectionShowBuffRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ElectionShowBuff::class);
    }

    // /**
    //  * @return ElectionShowBuff[] Returns an array of ElectionShowBuff objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ElectionShowBuff
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
