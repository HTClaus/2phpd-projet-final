<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Registration;
use App\Entity\Tournament;
use App\Entity\User;
use App\Form\TournamentCreateFormType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

class RegistrationController extends AbstractController
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
    #[Route('/registration/{tri}', name: 'app_registration')]
    public function index($tri,ManagerRegistry $doctrine,SessionInterface $session): Response
    {
        $response = $this->userAcces($session);
        if ($response !== null) {
            return $response;
        }
        $user = $doctrine->getManager()->getRepository(User::class)->findOneBy(['id' => $session->get('id')]);
        if ($tri =="upcoming"){
            $lesRegistrations = $doctrine->getRepository(Registration::class)->findRegistrationsForTournamentsAfterToday($user);
        }
        elseif($tri == "start"){
            $lesRegistrations = $doctrine->getRepository(Registration::class)->findRegistrationsForTournamentsBeforeToday($user);
        }
        else {
            $lesRegistrations = $doctrine->getManager()->getRepository(Registration::class)->findBy(['player' => $user]);
        }
        return $this->render('registration/index.html.twig', [
            'lesRegistrations'=> $lesRegistrations,
        ]);
    }
    #[Route('/registration/register/{id}', name: 'app_registration_id')]
    public function registerTournament($id, ManagerRegistry $doctrine,SessionInterface $session): Response
    {
        $response = $this->userAcces($session);
        if ($response !== null) {
            return $response;
        }
        $registration = new Registration();
        $today = \DateTime::createFromFormat('Y-m-d', date('Y-m-d'));
        $user = $doctrine->getManager()->getRepository(User::class)->findOneBy(['id' => $session->get('id')]);
        $tournament = $doctrine->getManager()->getRepository(Tournament::class)->findOneBy(['id' => $id]);
        $alreadyRegister = $doctrine->getManager()->getRepository(Registration::class)->findOneBy(['tournament' => $tournament,'player' => $user]);
        if (!$alreadyRegister && $tournament) {
            $entityManager = $doctrine->getManager();
            $registration->setRegistrationDate($today);
            $registration->setStatus("en attente");
            $registration->setPlayer($user);
            $registration->setTournament($tournament);
            $entityManager->persist($registration);
            $entityManager->flush();
            return $this->redirectToRoute('app_tournament');
        }else{
            $message = "You can't register for this tournament";
        }
        return $this->redirectToRoute('app_tournament');
    }
    #[Route('/registration/participate/{id}', name: 'app_participate_tournament', methods:['GET'])]
    public function participateTournament($id, ManagerRegistry $doctrine, SessionInterface $session): Response
    {
        $response = $this->userAcces($session);
        if ($response !== null) {
            return $response;
        }
        $today = \DateTime::createFromFormat('Y-m-d', date('Y-m-d'));
        $entityManager = $doctrine->getManager();
        $user = $doctrine->getManager()->getRepository(User::class)->findOneBy(['id'=>$session->get('id')]);
        $tournament = $doctrine->getManager()->getRepository(Tournament::class)->findOneBy(['id'=>$id]);
        $game = $doctrine->getManager()->getRepository(Game::class)->findOneBy(['tournament'=>$tournament , 'player2'=>null]);
        $alreadyParticipe= $doctrine->getManager()->getRepository(Registration::class)->findOneBy(['tournament'=>$id,'status'=>"participe",'player'=>$user]);
        $registration = $doctrine->getManager()->getRepository(Registration::class)->findOneBy(['tournament'=>$id,'player'=>$user]);
        $idTournament = $tournament->getId();
        if ($registration !="refuser" || $registration !="participe"){
            return $this->redirectToRoute("app_match", ['id' => $idTournament]);
        }
        if (!$alreadyParticipe) {
            if ($game) {
                $registration->setStatus("participe");
                $game->setStatus("complet");
                $game->setPlayer2($user);
                $game->setMatchDate($today);
                $entityManager->persist($game);
                $entityManager->flush();
            } else {
                $registration->setStatus("participe");
                $game = new game();
                $game->setPlayer1($user);
                $game->setStatus("non complet");
                $game->setPlayer2(null);
                $game->setTournament($tournament);
                $game->setScorePlayer1(0);
                $game->setScorePlayer2(0);
                $entityManager->persist($game);
                $entityManager->flush();
            }
        }
        return $this->redirectToRoute("app_match", ['id' => $idTournament]);
    }
    #[Route('/registrations/specific_tournament', name: 'api_register_tournament', methods: ['POST'])]
    public function apiRegisterTournament(Request $request, ManagerRegistry $doctrine): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['playerId']) || !isset($data['tournamentId'])) {
            return new JsonResponse(['error' => 'Player ID or Tournament ID is missing in the request body'], Response::HTTP_BAD_REQUEST);
        }

        $playerId = $data['playerId'];
        $tournamentId = $data['tournamentId'];

        $entityManager = $doctrine->getManager();
        $tournament = $entityManager->getRepository(Tournament::class)->find($tournamentId);
        $player = $entityManager->getRepository(User::class)->find($playerId);

        if (!$tournament || !$player) {
            return new JsonResponse(['error' => 'Invalid tournament or player'], Response::HTTP_BAD_REQUEST);
        }

        $registration = new Registration();
        $registration->setRegistrationDate(new \DateTime());
        $registration->setStatus("en attente");
        $registration->setPlayer($player);
        $registration->setTournament($tournament);

        $entityManager->persist($registration);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Player registered successfully'], Response::HTTP_OK);
    }
    #[Route('/registration/unregister/{id}', name: 'app_unregister_tournament', methods:['GET', 'DELETE'])]
    public function unregisterTournament($id, ManagerRegistry $doctrine, SessionInterface $session): Response
    {
        $response = $this->userAcces($session);
        if ($response !== null) {
            return $response;
        }
        $entityManager = $doctrine->getManager();
        $user = $doctrine->getManager()->getRepository(User::class)->findOneBy(['id' => $session->get('id')]);
        $tournament = $doctrine->getManager()->getRepository(Tournament::class)->findOneBy(['id' => $id]);
        $registration = $doctrine->getManager()->getRepository(Registration::class)->findOneBy(['tournament' => $tournament, 'player' => $user]);
        
        if ($registration) {
            $entityManager->remove($registration);
            $entityManager->flush();
        }
        
        return $this->redirectToRoute('app_tournament');
    }
}
