<?php

namespace App\Repository;

use App\Entity\Game;
use App\Entity\Tournament;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Game>
 *
 * @method Game|null find($id, $lockMode = null, $lockVersion = null)
 * @method Game|null findOneBy(array $criteria, array $orderBy = null)
 * @method Game[]    findAll()
 * @method Game[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }
    public function findUncompletedGameByTournamentAndUser(Tournament $tournament, User $user): ?Game
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.tournament = :tournament')
            ->andWhere('g.status = :status')
            ->andWhere('g.player1 = :user OR g.player2 = :user')
            ->setParameter('tournament', $tournament)
            ->setParameter('status', 'non complet')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }
    public function findGamesForUserInTournament(User $user, Tournament $tournament)
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.player1', 'p1')
            ->leftJoin('g.player2', 'p2')
            ->andWhere('g.tournament = :tournament')
            ->andWhere('p1 = :user OR p2 = :user')
            ->setParameter('tournament', $tournament)
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
    public function findGamesForUserInTournamentNotFinish(User $user, Tournament $tournament)
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.player1', 'p1')
            ->leftJoin('g.player2', 'p2')
            ->andWhere('g.tournament = :tournament')
            ->andWhere('p1 = :user OR p2 = :user')
            ->andWhere('g.status = :status')
            ->setParameter('tournament', $tournament)
            ->setParameter('user', $user)
            ->setParameter('status', "complet")
            ->getQuery()
            ->getOneOrNullResult();
    }
    public function countUserWinsInTournament(User $user, Tournament $tournament): int
    {
        return $this->createQueryBuilder('g')
            ->select('COUNT(g.id)')
            ->where('g.tournament = :tournament')
            ->andWhere('g.status = :status')
            ->andWhere('(g.player1 = :user AND g.scorePlayer1 > g.scorePlayer2) OR (g.player2 = :user AND g.scorePlayer2 > g.scorePlayer1)')
            ->setParameter('tournament', $tournament)
            ->setParameter('status', 'complet')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }



    //    /**
    //     * @return Game[] Returns an array of Game objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('g')
    //            ->andWhere('g.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('g.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Game
    //    {
    //        return $this->createQueryBuilder('g')
    //            ->andWhere('g.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
