<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Registration;
use App\Entity\Tournament;
use App\Entity\User;
use App\Form\GameAdminFormType;
use App\Form\TournamentCreateFormType;
use App\Form\UserLoginFormType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

class AdminController extends AbstractController
{
    private function userAcces(SessionInterface $session): ?Response
    {
        $userStatus = $session->get('status');

        if ($userStatus === null) {
            $erreur = "Veuillez vous connecter pour accéder à cette page." .
                " \n Redirection vers la page de connexion";
            $redirection = 'app_player_login';
        } elseif ($userStatus != "admin") {
            $erreur = "Vous n'avez pas accès à cette page" .
                " \n Redirection vers la page d'accueil";
            $redirection = 'app_player';
        } else {
            $erreur = null;
        }

        if ($erreur !== null) {
            $this->addFlash('erreur', $erreur);
            return $this->redirectToRoute($redirection);
        }
        return null;
    }
    #[Route('manageplayers/list', name: 'app_manage_players')]
    public function listPlayer(Request $request, ManagerRegistry $doctrine, SessionInterface $session): Response
    {
        $response = $this->userAcces($session);
        if ($response !== null) {
            return $response;
        }
        $players = $doctrine->getManager()->getRepository(User::class)->findAllNonAdminUsers();
        return $this->render('admin/listPlayers.html.twig',[
            'players'=>$players
        ]);
    }
    #[Route('manageplayers/ban/{id}', name: 'app_manage_players_ban')]
    public function banPlayer($id, ManagerRegistry $doctrine, SessionInterface $session): Response
    {
        $response = $this->userAcces($session);
        if ($response !== null) {
            return $response;
        }
        $player = $doctrine->getManager()->getRepository(User::class)->findOneBy(['id'=>$id]);
        $player->setStatus("banni");
        $doctrine->getManager()->persist($player);
        $doctrine->getManager()->flush();
        return $this->redirectToRoute('app_manage_players');
    }
    #[Route('manageplayers/unban/{id}', name: 'app_manage_players_unban')]
    public function unBanPlayer($id, ManagerRegistry $doctrine, SessionInterface $session): Response
    {
        $response = $this->userAcces($session);
        if ($response !== null) {
            return $response;
        }
        $player = $doctrine->getManager()->getRepository(User::class)->findOneBy(['id'=>$id]);
        $player->setStatus("inactif");
        $doctrine->getManager()->persist($player);
        $doctrine->getManager()->flush();
        return $this->redirectToRoute('app_manage_players');
    }
    #[Route('managetournaments/list', name: 'app_manage_tournaments')]
    public function listTournaments( ManagerRegistry $doctrine, SessionInterface $session): Response
    {
        $response = $this->userAcces($session);
        if ($response !== null) {
            return $response;
        }
        $tournaments = $doctrine->getManager()->getRepository(Tournament::class)->findAll();
        return $this->render('admin/listTournaments.html.twig', [
            'tournaments'=>$tournaments
        ]);
    }
    #[Route('managetournaments/cancel/{id}', name: 'app_manage_tournaments_cancel')]
    public function cancelTournament($id, ManagerRegistry $doctrine, SessionInterface $session): Response
    {
        $response = $this->userAcces($session);
        if ($response !== null) {
            return $response;
        }
        $tournament = $doctrine->getManager()->getRepository(Tournament::class)->findOneBy(['id'=>$id]);
        $tournament->setStatus("cancel");
        $doctrine->getManager()->persist($tournament);
        $doctrine->getManager()->flush();
        return $this->redirectToRoute('app_manage_tournaments');
    }
    #[Route('managetournaments/uncancel/{id}', name: 'app_manage_tournaments_uncancel')]
    public function unCancelTournament($id, ManagerRegistry $doctrine, SessionInterface $session): Response
    {
        $response = $this->userAcces($session);
        if ($response !== null) {
            return $response;
        }
        $tournament = $doctrine->getManager()->getRepository(Tournament::class)->findOneBy(['id'=>$id]);
        $tournament->setStatus("en attente");
        $doctrine->getManager()->persist($tournament);
        $doctrine->getManager()->flush();
        return $this->redirectToRoute('app_manage_tournaments');
    }
    #[Route('managetournaments/modify/{id}', name: 'app_manage_tournaments_modify')]
    public function modifyTournament($id,Request $request, ManagerRegistry $doctrine, SessionInterface $session): Response
    {
        $response = $this->userAcces($session);
        if ($response !== null) {
            return $response;
        }
        $tournament = $doctrine->getManager()->getRepository(Tournament::class)->findOneBy(['id'=>$id]);
        $form = $this->createForm(TournamentCreateFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $form->getData();
            $tournament->setStatus('en attente');
            $tournament->setTournamentName($form->get('tournamentName')->getData());
            $tournament->setDescritpion($form->get('descritpion')->getData());
            $tournament->setnomJeu($form->get('nomJeu')->getData());
            $tournament->setEndDate($form->get('endDate')->getData());
            $tournament->setStartDate($form->get('startDate')->getData());
            $tournament->setLocation($form->get('location')->getData());
            $tournament->setMaxParticipant($form->get('maxParticipant')->getData());
            $entityManager=$doctrine->getManager();
            $entityManager->persist($tournament);
            $entityManager->flush();
            return $this->redirectToRoute('app_manage_tournaments');
        }
        return $this->render('admin/modifyTournament.html.twig', [
            'tournament'=>$tournament,
            'form' => $form->createView()
        ]);
    }
    #[Route('manageregistrations/list', name: 'app_manage_registrations')]
    public function RegistrationTournament( ManagerRegistry $doctrine, SessionInterface $session): Response
    {
        $response = $this->userAcces($session);
        if ($response !== null) {
            return $response;
        }
        $registrations = $doctrine->getManager()->getRepository(Registration::class)->findBy(['status'=>"en attente"]);
        return $this->render('admin/listRegistrations.html.twig',[
            'registrations'=>$registrations
        ]);
    }
    #[Route('manageregistrations/refuse/{id}/{tournamentId}', name: 'app_manage_registrations_refuse')]
    public function RegistrationTournamentRefuse( $id,$tournamentId,ManagerRegistry $doctrine, SessionInterface $session): Response
    {
        $response = $this->userAcces($session);
        if ($response !== null) {
            return $response;
        }
        $tournament = $doctrine->getManager()->getRepository(Tournament::class)->findOneBy(['id'=>$tournamentId]);
        $user = $doctrine->getManager()->getRepository(User::class)->findOneBy(['id'=>$id]);
        $registration = $doctrine->getManager()->getRepository(Registration::class)->findOneBy(['player'=>$user,'tournament'=>$tournament]);
        $registration->setStatus("refuser");
        $doctrine->getManager()->persist($registration);
        $doctrine->getManager()->flush();
        return $this->redirectToRoute('app_manage_registrations');
    }
    #[Route('manageregistrations/confirm/{id}/{tournamentId}', name: 'app_manage_registrations_confirm')]
    public function RegistrationTournamentconfirm( $id,$tournamentId,ManagerRegistry $doctrine, SessionInterface $session): Response
    {
        $response = $this->userAcces($session);
        if ($response !== null) {
            return $response;
        }
        $tournament = $doctrine->getManager()->getRepository(Tournament::class)->findOneBy(['id'=>$tournamentId]);
        $user = $doctrine->getManager()->getRepository(User::class)->findOneBy(['id'=>$id]);
        $registration = $doctrine->getManager()->getRepository(Registration::class)->findOneBy(['player'=>$user,'tournament'=>$tournament]);
        $registration->setStatus("confirmer");
        $doctrine->getManager()->persist($registration);
        $doctrine->getManager()->flush();
        return $this->redirectToRoute('app_manage_registrations');
    }
    #[Route('managematchs/list', name: 'app_manage_matchs')]
    public function matchList(ManagerRegistry $doctrine, SessionInterface $session): Response
    {
        $response = $this->userAcces($session);
        if ($response !== null) {
            return $response;
        }
        $matchs = $doctrine->getManager()->getRepository(Game::class)->findBy(['status' => "complet"]);
        return $this->render('admin/listMatchs.html.twig',[
            'matchs'=>$matchs
        ]);
    }
    #[Route('managematchs/modify/{gameId}', name: 'app_manage_matchs_modify')]
    public function matchModify($gameId,ManagerRegistry $doctrine,Request $request, SessionInterface $session): Response
    {
        $response = $this->userAcces($session);
        if ($response !== null) {
            return $response;
        }
        $game = $doctrine->getManager()->getRepository(Game::class)->findOneBy(['id'=>$gameId]);
        $form = $this->createForm(GameAdminFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('scorePlayer1')->getData()!=0 && $form->get('scorePlayer2')->getData()){
                $game->setStatus("terminer");
            }
            $game->setScorePlayer1($form->get('scorePlayer1')->getData());
            $game->setScorePlayer2($form->get('scorePlayer2')->getData());
            $doctrine->getManager()->persist($game);
            $doctrine->getManager()->flush();
            return $this->redirectToRoute('app_manage_matchs');
        }
        return $this->render('admin/modifyMatchs.html.twig',[
            'match'=>$game,
            'form'=>$form
        ]);
    }
}
