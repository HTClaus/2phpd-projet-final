<?php

namespace App\Repository;

use App\Entity\Game;
use App\Entity\Registration;
use App\Entity\Tournament;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Registration>
 *
 * @method Registration|null find($id, $lockMode = null, $lockVersion = null)
 * @method Registration|null findOneBy(array $criteria, array $orderBy = null)
 * @method Registration[]    findAll()
 * @method Registration[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RegistrationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Registration::class);
    }
    public function findRegistrationsForTournamentsAfterToday($playerId): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.tournament', 't')
            ->andWhere('r.player = :playerId')
            ->andWhere('t.startDate > :today')
            ->setParameter('playerId', $playerId)
            ->setParameter('today', new \DateTime())
            ->getQuery()
            ->getResult();
    }
    public function findRegistrationsForTournamentsBeforeToday($playerId): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.tournament', 't')
            ->andWhere('r.player = :playerId')
            ->andWhere('t.startDate < :today')
            ->setParameter('playerId', $playerId)
            ->setParameter('today', new \DateTime())
            ->getQuery()
            ->getResult();

    }

    //    /**
    //     * @return Registration[] Returns an array of Registration objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Registration
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
