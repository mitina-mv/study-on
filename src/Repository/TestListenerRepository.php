<?php

namespace App\Repository;

use App\Entity\TestListener;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TestListener>
 *
 * @method TestListener|null find($id, $lockMode = null, $lockVersion = null)
 * @method TestListener|null findOneBy(array $criteria, array $orderBy = null)
 * @method TestListener[]    findAll()
 * @method TestListener[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TestListenerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TestListener::class);
    }

    //    /**
    //     * @return TestListener[] Returns an array of TestListener objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?TestListener
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
