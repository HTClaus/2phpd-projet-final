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
    #[Route('/tournament', name: 'app_tournament')]
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
    #[Route('/tournament/create', name: 'app_tournament_create')]
    public function createTournament(Request $request, ManagerRegistry $doctrine, SessionInterface $session): Response
    {
        $response = $this->userAcces($session);
        if ($response !== null) {
            return $response;
        }
        $tournament = new Tournament();
        $form = $this->createForm(TournamentCreateFormType::class,$tournament);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userRepository = $doctrine->getManager()->getRepository(User::class);
            $user = $userRepository->findOneBy(['id' => $session->get('id')]);

            $entityManager = $doctrine->getManager();
            $tournament->setStatus("en attente");
            $tournament->setOrganizer($user);
            $tournament->setWinner(null);
            $entityManager->persist($tournament);
            $entityManager->flush();
            return $this->redirectToRoute('app_tournament');
        }
        return $this->render('tournament/createTournament.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
