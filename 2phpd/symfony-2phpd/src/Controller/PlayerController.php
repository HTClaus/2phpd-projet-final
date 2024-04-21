<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserLoginFormType;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\UserRegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;


class PlayerController extends AbstractController
{
    #[Route('/player', name: 'app_player', methods:['GET'])]
    public function index(SessionInterface $session): Response
    {
        $erreur = $session->getFlashBag()->get('erreur');
        $session->remove('erreur');
        if ($erreur) {
            return $this->render('player/index.html.twig', [
                'erreur' => $erreur,
            ]);
        }
        return $this->render('player/index.html.twig', [
            'controller_name' => 'PlayerController',
        ]);
    }

    #[Route('/register', name: 'app_player_register', methods: ['POST'])]
    public function registerPlayer(Request $request, ManagerRegistry $doctrine, SessionInterface $session): Response
    {
        $user = new User();
        $form = $this->createForm(UserRegistrationFormType::class, $user);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $userRepository = $doctrine->getManager()->getRepository(User::class);
            $userFirstName = $userRepository->findOneBy(['firstName' => $data->getFirstName()]);
            $userLastName = $userRepository->findOneBy(['lastName' => $data->getLastName()]);
            $userPassword = $userRepository->findOneBy(['password' => md5($data->getPassword() . '15')]);

            if ($userPassword) {
                $message = "Ce mot de passe est déjà utilisé !";
                return new JsonResponse(['message' => $message], Response::HTTP_BAD_REQUEST);
            }
            if ($userFirstName) {
                $message = "Ce prénom est déjà utilisé !";
                return new JsonResponse(['message' => $message], Response::HTTP_BAD_REQUEST);
            } elseif ($userLastName) {
                $message = "Ce nom de famille est déjà utilisé !";
                return new JsonResponse(['message' => $message], Response::HTTP_BAD_REQUEST);
            }
            $userAddress = $userRepository->findOneBy(['emailAddress' => $data->getEmailAddress()]);
            $userUsername = $userRepository->findOneBy(['username' => $data->getUsername()]);
            
            if ($userAddress) {
                $message = "Cette adresse e-mail est déjà utilisée !";
                return new JsonResponse(['message' => $message], Response::HTTP_BAD_REQUEST);
            } elseif ($userUsername) {
                $message = "Ce nom d'utilisateur est déjà utilisé !";
                return new JsonResponse(['message' => $message], Response::HTTP_BAD_REQUEST);
            } else {
                $entityManager = $doctrine->getManager();
                $user->setPassword(md5($user->getPassword() . '15')); // Avoid using MD5 for password hashing in production
                $user->setStatus("actif");
                $entityManager->persist($user);
                $entityManager->flush();
                return new JsonResponse(['message' => 'Utilisateur enregistré avec succès'], Response::HTTP_CREATED);
            }
        }

        return $this->render('player/registerPlayer.html.twig', [
            'form' => $form->createView(),
        ]);

    }
    #[Route('/profile', name: 'app_player_profile', methods:['GET'])]
    public function profile(SessionInterface $session): Response
    {
        $profileData = [
            'id' => $session->get('id'),
            'username' => $session->get('username'),
            'emailaddress' => $session->get('emailaddress'),
            'status' => $session->get('status'),
        ];

        return new JsonResponse($profileData);
    }
    
    #[Route('/profile/update', name: 'profile_update', methods: ['PUT'])]
    public function updateProfile(Request $request, ManagerRegistry $doctrine, SessionInterface $session): Response
    {
        $userId = $session->get('id');
        
        if (!$userId) {
            return new JsonResponse(['error' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $userRepository = $doctrine->getRepository(User::class);
        $user = $userRepository->find($userId);

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur introuvable'], Response::HTTP_NOT_FOUND);
        }

        $form = $this->createForm(UserRegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $userAddress = $userRepository->findOneBy(['emailAddress' => $data->getEmailAddress()]);
            $userUsername = $userRepository->findOneBy(['username' => $data->getUsername()]);

            if ($userAddress && $userAddress->getId() !== $userId) {
                $message = "Cette adresse e-mail est déjà utilisée !";
                return new JsonResponse(['message' => $message], Response::HTTP_BAD_REQUEST);
            } elseif ($userUsername && $userUsername->getId() !== $userId) {
                $message = "Ce nom d'utilisateur est déjà utilisé !";
                return new JsonResponse(['message' => $message], Response::HTTP_BAD_REQUEST);
            } else {
                $entityManager = $doctrine->getManager();
                $entityManager->persist($user);
                $entityManager->flush();
                return new JsonResponse(['message' => 'Profil mis à jour avec succès'], Response::HTTP_OK);
            }
        }

        return $this->render('player/updateProfile.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/profile/delete', name: 'app_player_profile_delete', methods: ['DELETE'])]
    public function deleteProfile(ManagerRegistry $doctrine, SessionInterface $session): Response
    {

        $userId = $session->get('id');
        
        if (!$userId) {
   
            return $this->redirectToRoute('app_player_login');
        }

        $userRepository = $doctrine->getRepository(User::class);
        $user = $userRepository->find($userId);

        if (!$user) {
            return $this->redirectToRoute('app_player');
        }

        $entityManager = $doctrine->getManager();
        $entityManager->remove($user);
        $entityManager->flush();

        $session->clear();

        return $this->redirectToRoute('app_player');
    }
    

    #[Route('/login', name: 'app_player_login')]
    public function loginPlayer(Request $request, ManagerRegistry $doctrine, SessionInterface $session): Response
    {

        $form = $this->createForm(UserLoginFormType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $userRepository = $doctrine->getManager()->getRepository(User::class);

            $user = $userRepository->findOneBy(['emailAddress' => $data->getEmailAddress()]);
            if ($user && $user->getPassword() == (MD5($data->getPassword().'15'))) {
                if ($user->getStatus() == "banni"){
                    $message = "Votre compte est banni !";
                    return $this->render('player/loginPlayer.html.twig', [
                        'message' => $message,
                        'form' => $form->createView(),
                    ]);
                }
                if ($user->getStatus()=="inactif"){
                    $user->setStatus("actif");
                    $entityManager = $doctrine->getManager();
                    $entityManager->persist($user);
                    $entityManager->flush();
                }
                $session->set('id', $user->getId());
                $session->set('username', $user->getUsername());
                $session->set('status', $user->getStatus());
                return $this->redirectToRoute('app_player');
            } else {
                $message="Mail ou mode passe incorrect";
                return $this->render('player/loginPlayer.html.twig',[
                    'message' => $message,
                    'form' => $form->createView(),]);

            }
        }

        return $this->render('player/loginPlayer.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/logout', name: 'app_player_logout')]
    public function logout(SessionInterface $session): Response
    {
        $session->clear();
        return $this->redirectToRoute('app_player');
    } 
    
}

