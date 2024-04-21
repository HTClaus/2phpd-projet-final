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
use Symfony\Component\HttpFoundation\JsonResponse;

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
    #[Route('/match/{id}', name: 'app_match', methods:['GET'])]
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
    #[Route('/match/create/{id}', name: 'app_match_create', methods:['POST'])]
    public function createMatch($id, Request $request, ManagerRegistry $doctrine, SessionInterface $session): Response
    {
        $response = $this->userAcces($session);
        if ($response !== null) {
            return $response;
        }

        $entityManager = $doctrine->getManager();
        $userId = $session->get('id');
        $data = json_decode($request->getContent(), true);

        if (!isset($data['playerId'])) {
            return new JsonResponse(['error' => 'L\'identifiant du joueur est manquant dans le corps de la requête'], Response::HTTP_BAD_REQUEST);
        }

        $playerId = $data['playerId'];
        $user = $entityManager->getRepository(User::class)->find($userId);
        $tournament = $entityManager->getRepository(Tournament::class)->find($id);

        if (!$user || !$tournament) {
            return new JsonResponse(['error' => 'Utilisateur ou tournoi invalide'], Response::HTTP_BAD_REQUEST);
        }

        $game = $entityManager->getRepository(Game::class)->findOneBy(['tournament' => $tournament, 'player2' => null]);

        if ($game) {
            $game->setPlayer2($user);
        } else {
            $game = new Game();
            $game->setPlayer1($user);
            $game->setStatus("non complet");
            $game->setPlayer2(null);
            $game->setTournament($tournament);
            $game->setScorePlayer1(0);
            $game->setScorePlayer2(0);
            $entityManager->persist($game);
        }

        $entityManager->flush();

        return new JsonResponse(['message' => 'Match créé avec succès'], Response::HTTP_OK);
    }
    #[Route('/tournaments/{idTournament}/games/{idGame}', name: 'game_details', methods:['GET'])]
    public function getGameDetails($idTournament, $idGame, ManagerRegistry $doctrine): Response
    {
        $game = $doctrine->getManager()->getRepository(Game::class)->findOneBy(['id' => $idGame, 'tournament' => $idTournament]);
        if (!$game) {
            return new Response('Game not found', Response::HTTP_NOT_FOUND);
        }
        $player1 = $game->getPlayer1();
        $player2 = $game->getPlayer2();
        $tournament = $game->getTournament();
        $responseData = [
            'id' => $game->getId(),
            'player1' => $player1 ? $player1->getId() : null,
            'player2' => $player2 ? $player2->getId() : null,
            'tournament' => $tournament ? $tournament->getId() : null,
        ];
        return new Response(json_encode($responseData), Response::HTTP_OK);
    }
    #[Route('/tournaments/{idTournament}/games/{idGame}', name: 'update_game', methods:['PUT'])]
    public function updateGame($idTournament, $idGame, Request $request, ManagerRegistry $doctrine, SessionInterface $session): Response
    {
        $response = $this->userAcces($session);
        if ($response !== null) {
            return $response;
        }
        
        $entityManager = $doctrine->getManager();
        $game = $doctrine->getManager()->getRepository(Game::class)->findOneBy(['id' => $idGame, 'tournament' => $idTournament]);
        
        if (!$game) {
            return new JsonResponse(['error' => 'Game not found'], Response::HTTP_NOT_FOUND);
        }
        
        $user = $doctrine->getManager()->getRepository(User::class)->findOneBy(['id' => $session->get('id')]);
        
        if ($user !== $game->getPlayer1() && !$user->isAdmin()) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }
        
        $data = json_decode($request->getContent(), true);
        $scorePlayer1 = $data['scorePlayer1'] ?? null;
        $scorePlayer2 = $data['scorePlayer2'] ?? null;
        
        if ($scorePlayer1 !== null) {
            $game->setScorePlayer1($scorePlayer1);
        }
        
        if ($scorePlayer2 !== null) {
            $game->setScorePlayer2($scorePlayer2);
        }
        
        $entityManager->persist($game);
        $entityManager->flush();
        
        return new JsonResponse(['message' => 'Game updated successfully'], Response::HTTP_OK);
    }
    #[Route('/match/delete/{id}', name: 'app_match_delete', methods:['DELETE'])]
    public function deleteMatch($id, ManagerRegistry $doctrine, SessionInterface $session): Response
    {
        $response = $this->userAcces($session);
        if ($response !== null) {
            return $response;
        }
        $entityManager = $doctrine->getManager();
        $user = $doctrine->getManager()->getRepository(User::class)->findOneBy(['id' => $session->get('id')]);
        $tournament = $doctrine->getManager()->getRepository(Tournament::class)->findOneBy(['id' => $id]);
        $game = $doctrine->getManager()->getRepository(Game::class)->findOneBy(['tournament' => $tournament, 'player2' => $user]);
        if ($game) {
            $entityManager->remove($game);
            $entityManager->flush();
        }
        return $this->redirectToRoute('app_match', ['id' => $id]);
    }
    
}
