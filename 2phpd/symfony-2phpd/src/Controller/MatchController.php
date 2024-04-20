<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Registration;
use App\Entity\Tournament;
use App\Entity\User;
use App\Form\MatchScoreFormType;
use App\Form\TournamentCreateFormType;
use App\Form\UserRegistrationFormType;
use App\Repository\GameRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

class MatchController extends AbstractController
{
    private function userAcces(SessionInterface $session): ?Response
    {
        $userStatus = $session->get('status');

        if ($userStatus === null) {
            $erreur = "Veuillez vous connecter pour accéder à cette page." .
                " \n Redirection vers la page de connexion";
            $redirection = 'app_player_login';
        } elseif ($userStatus != "actif") {
            $erreur = "Vous n'avez pas accès à cette page" .
                " \n Redirection vers la page d'accueil";
            $redirection = 'app_player';
        } else {
            $erreur = null;
        }

        if ($erreur !== null) {
            $session->getFlashBag()->add('erreur', $erreur);
            return $this->redirectToRoute($redirection);
        }

        return null;
    }
    #[Route('/match/{id}', name: 'app_match')]
    public function index($id, Request $request, SessionInterface $session, ManagerRegistry $doctrine): Response
    {
        $response = $this->userAcces($session);
        if ($response !== null) {
            return $response;
        }
        $form = $this->createForm(MatchScoreFormType::class);
        $form->handleRequest($request);
        $user = $doctrine->getManager()->getRepository(User::class)->findOneBy(['id' => $session->get('id')]);
        $tournament = $doctrine->getManager()->getRepository(Tournament::class)->findOneBy(['id' => $id]);
        $matchs = $doctrine->getManager()->getRepository(Game::class)->findGamesForUserInTournament($user, $tournament);
        $game = $doctrine->getManager()->getRepository(Game::class)->findGamesForUserInTournamentNotFinish($user, $tournament);
        if ($game) {
            if ($game->getPlayer1() == $user) {
                $alreadyScorePlayer = $doctrine->getManager()->getRepository(Game::class)->findOneBy(['id' => $game->getId(), 'scorePlayer1' => 0]);
            } else {
                $alreadyScorePlayer = $doctrine->getManager()->getRepository(Game::class)->findOneBy(['id' => $game->getId(), 'scorePlayer2' => 0]);
            }
            if ($alreadyScorePlayer) {
                if ($form->isSubmitted() && $form->isValid()) {
                    $data = $form->getData();
                    $game = $doctrine->getManager()->getRepository(Game::class)->findGamesForUserInTournamentNotFinish($user, $tournament);
                    if ($game->getPlayer1() == $user) {
                        $game->setScorePlayer1($data['scorePlayer']);
                        if ($game->getScorePlayer2() != 0) {
                            $game->setStatus("terminer");
                        }
                    } else {
                        $game->setScorePlayer2($data['scorePlayer']);
                        if ($game->getScorePlayer1() != 0) {
                            $game->setStatus("terminer");
                        }
                    }
                    $entityManager = $doctrine->getManager();
                    $entityManager->persist($game);
                    $entityManager->flush();
                    if ($game->getStatus() == "terminer") {
                        $registrationPlayer1 = $doctrine->getManager()->getRepository(Registration::class)->findOneBy(['player' => $game->getPlayer1(), 'tournament' => $tournament]);
                        $registrationPlayer2 = $doctrine->getManager()->getRepository(Registration::class)->findOneBy(['player' => $game->getPlayer2(), 'tournament' => $tournament]);
                        if ($game->getScorePlayer1() > $game->getScorePlayer2()) {
                            $i = $doctrine->getManager()->getRepository(Game::class)->countUserWinsInTournament($game->getPlayer1(), $tournament);
                            $i += 1;
                            $winner = $registrationPlayer1;
                            $registrationPlayer1->setStatus("Manche " . $i);
                            $registrationPlayer2->setStatus("Perdu");
                            $entityManager->persist($registrationPlayer1);
                            $entityManager->persist($registrationPlayer2);
                        } else {
                            $i = $doctrine->getManager()->getRepository(Game::class)->countUserWinsInTournament($game->getPlayer2(), $tournament);
                            $i += 1;
                            $winner = $registrationPlayer2;
                            $registrationPlayer2->setStatus("Manche " . $i);
                            $registrationPlayer1->setStatus("Perdu");
                            $entityManager->persist($registrationPlayer1);
                            $entityManager->persist($registrationPlayer2);
                        }
                        $entityManager->flush();

                        $registrationWinnerPlayer = $doctrine->getManager()->getRepository(Registration::class)->findOneBy(['player' => $winner->getPlayer(), 'tournament' => $tournament]);
                        $registrationsStatusSameThanWinner= $doctrine->getManager()->getRepository(Registration::class)->findBy(['status'=>$winner->getStatus(),'tournament'=>$tournament]);
                        foreach ($registrationsStatusSameThanWinner as $item) {
                            $userSameStatusForRegistration=$item->getPlayer();
                            $gameUserSameStatusForRegistration = $doctrine->getManager()->getRepository(Game::class)->findOneBy(['tournament'=>$tournament , 'player2'=>null,'player1'=>$userSameStatusForRegistration]);
                            if ($gameUserSameStatusForRegistration){
                                break;
                            }
                        }
                        if ($gameUserSameStatusForRegistration) {
                            $today = \DateTime::createFromFormat('Y-m-d', date('Y-m-d'));
                            $gameUserSameStatusForRegistration->setStatus("complet");
                            $gameUserSameStatusForRegistration->setPlayer2($registrationWinnerPlayer->getPlayer());
                            $gameUserSameStatusForRegistration->setMatchDate($today);
                            $entityManager->persist($gameUserSameStatusForRegistration);
                            $entityManager->flush();
                        } else {
                            $newGame = new game();
                            $newGame->setPlayer1($registrationWinnerPlayer->getPlayer());
                            $newGame->setStatus("non complet");
                            $newGame->setPlayer2(null);
                            $newGame->setTournament($tournament);
                            $newGame->setScorePlayer1(0);
                            $newGame->setScorePlayer2(0);
                            $entityManager->persist($newGame);
                            $entityManager->flush();
                        }

                        return $this->render('match/index.html.twig', [
                            'matchs' => $matchs,
                        ]);
                    }
                    return $this->render('match/index.html.twig', [
                        'matchs' => $matchs,
                    ]);
                }
                return $this->render('match/index.html.twig', [
                    'matchs' => $matchs,
                    'form' => $form->createView(),
                ]);
            }
            return $this->render('match/index.html.twig', [
                'matchs' => $matchs,
            ]);
        }
        return $this->render('match/index.html.twig', [
            'matchs' => $matchs,
        ]);
    }
}
