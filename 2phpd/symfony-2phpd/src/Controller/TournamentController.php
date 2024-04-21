<?php

namespace App\Controller;

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


class TournamentController extends AbstractController
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
    #[Route('/tournament', name: 'app_tournament' , methods:['GET'])]
    public function index(ManagerRegistry $doctrine,SessionInterface $session): Response
    {
        $response = $this->userAcces($session);
        if ($response !== null) {
            return $response;
        }
        $lestournaments = $doctrine->getRepository(Tournament::class)->findTournamentsStartingAfterToday();

        return $this->render('tournament/index.html.twig', [
            'lesTournaments' => $lestournaments
        ]);
    }
    
    #[Route('/tournament/create', name: 'app_tournament_create', methods: ['POST'])]
    public function createTournament(Request $request, ManagerRegistry $doctrine, SessionInterface $session): Response
    {
        $response = $this->userAcces($session);
        if ($response !== null) {
            return $response;
        }

        $tournament = new Tournament();
        $form = $this->createForm(TournamentCreateFormType::class, $tournament);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userRepository = $doctrine->getManager()->getRepository(User::class);
            $user = $userRepository->findOneBy(['id' => $session->get('id')]);
            $entityManager = $doctrine->getManager();
            $tournament->setStatus("Waiting");
            $tournament->setOrganizer($user);
            $tournament->setWinner(null);
            $entityManager->persist($tournament);
            $entityManager->flush();
            return new JsonResponse(['message' => 'Tournoi créé avec succès'], Response::HTTP_CREATED);
        }

        $errors = $this->getFormErrors($form);
        return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
    }
    #[Route('/tournament/{id}', name: 'app_tournament_show', methods:['GET'])]
    public function showTournament($id, ManagerRegistry $doctrine, SessionInterface $session): Response
    {
        $response = $this->userAcces($session);
        if ($response !== null) {
            return $response;
        }
        $tournament = $doctrine->getRepository(Tournament::class)->find($id);
        return $this->render('tournament/showTournament.html.twig', [
            'tournament' => $tournament
        ]);
    }
    #[Route('/tournament/{id}/update', name: 'app_tournament_update', methods: ['PUT'])]
    public function updateTournament($id, Request $request, ManagerRegistry $doctrine, SessionInterface $session): Response
    {
        $response = $this->userAcces($session);
        if ($response !== null) {
            return $response;
        }

        $tournament = $doctrine->getRepository(Tournament::class)->find($id);

        if (!$tournament) {
            return new JsonResponse(['error' => 'Tournoi non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $form = $this->createForm(TournamentCreateFormType::class, $tournament);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $tournament->setName($form->get('nomjeu')->getData());
            $tournament->setStartDate($form->get('startdate')->getData());
            $tournament->setEndDate($form->get('enddate')->getData());
            $tournament->setLocation($form->get('location')->getData());
            $tournament->setDescription($form->get('description')->getData());
            $tournament->setMaxParticipants($form->get('maxParticipants')->getData());
            $tournament->setStatus($form->get('status')->getData());
            $tournament->setOrganizer($form->get('organizer')->getData());
            $tournament->setWinner($form->get('winner')->getData());
            $entityManager = $doctrine->getManager();
            $entityManager->persist($tournament);
            $entityManager->flush();
            return new JsonResponse(['message' => 'Tournoi mis à jour avec succès'], Response::HTTP_OK);
        }
        $errors = $this->getFormErrors($form);
        return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
    }
    #[Route('/tournament/{id}/delete', name: 'app_tournament_delete', methods:['DELETE'])]
    public function deleteTournament($id, ManagerRegistry $doctrine, SessionInterface $session): Response
    {
        $response = $this->userAcces($session);
        if ($response !== null) {
            return $response;
        }
        $tournament = $doctrine->getRepository(Tournament::class)->find($id);
        $entityManager = $doctrine->getManager();
        $entityManager->remove($tournament);
        $entityManager->flush();
        return $this->redirectToRoute('app_tournament');
    }
    
}