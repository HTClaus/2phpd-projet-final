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

    #[Route('/register', name: 'app_player_register', methods:['POST'])]
    public function registerPlayer(Request $request,ManagerRegistry $doctrine,SessionInterface $session): ?Response
    {
        $user = new User();
        $form = $this->createForm(UserRegistrationFormType::class,$user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $userRepository = $doctrine->getManager()->getRepository(User::class);
            $userAddress = $userRepository->findOneBy(['emailAddress' => $data->getEmailAddress()]);
            $userUsername = $userRepository->findOneBy(['username' => $data->getUsername()]);
           if ($userAddress){
                $message="This email address is already use !";
            }
            elseif ($userUsername){
                $message="This username is already use !";
            }
            else{
                $entityManager = $doctrine->getManager();
                $user->setPassword((MD5($user->getPassword().'15')));
                $user->setStatus("actif");
                $entityManager->persist($user);
                $entityManager->flush();
                return $this->redirectToRoute('app_player_login');
            }
            return $this->render('player/registerPlayer.html.twig', [
                'form' => $form->createView(),
                'message' => $message
            ]);
        }
        return $this->render('player/registerPlayer.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/profile', name: 'app_player_profile', methods:['GET'])]
    public function profile(SessionInterface $session): Response
    {
        return $this->render('player/profile.html.twig', [
            'id' => $session->get('id'),
            'username' => $session->get('username'),
            'emailaddress' => $session->get('emailaddress'),
            'status' => $session->get('status'),
        ]);
    }
    #[Route('/profile/update', name: 'app_player_profile_update', methods:['PUT'])]
    public function updateProfile(Request $request, ManagerRegistry $doctrine, SessionInterface $session): Response
    {
        $userRepository = $doctrine->getManager()->getRepository(User::class);
        $user = $userRepository->findOneBy(['id' => $session->get('id')]);
        $form = $this->createForm(UserRegistrationFormType::class,$user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $userAddress = $userRepository->findOneBy(['emailAddress' => $data->getEmailAddress()]);
            $userUsername = $userRepository->findOneBy(['username' => $data->getUsername()]);
            if ($userAddress && $userAddress->getId() != $user->getId()){
                $message="This email address is already use !";
            }
            elseif ($userUsername && $userUsername->getId() != $user->getId()){
                $message="This username is already use !";
            }
            else{
                $entityManager = $doctrine->getManager();
                $user->setPassword((MD5($user->getPassword().'15')));
                $entityManager->persist($user);
                $entityManager->flush();
                return $this->redirectToRoute('app_player_profile');
            }
            return $this->render('player/updateProfile.html.twig', [
                'form' => $form->createView(),
                'message' => $message
            ]);
        }
        return $this->render('playe
        r/updateProfile.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/profile/delete', name: 'app_player_profile_delete', methods: ['DELETE'])]
    public function deleteProfile(ManagerRegistry $doctrine, SessionInterface $session): Response
    {
        // Récupérer l'utilisateur à partir de la session
        $userId = $session->get('id');
        
        if (!$userId) {
            // Rediriger ou gérer le cas où l'utilisateur n'est pas connecté
            // Par exemple, rediriger vers la page de connexion
            return $this->redirectToRoute('app_player_login');
        }

        $userRepository = $doctrine->getRepository(User::class);
        $user = $userRepository->find($userId);

        // Vérifier si l'utilisateur existe
        if (!$user) {
            // Gérer le cas où l'utilisateur n'existe pas
            // Par exemple, afficher un message d'erreur ou rediriger vers une autre page
            return $this->redirectToRoute('app_player');
        }

        $entityManager = $doctrine->getManager();
        $entityManager->remove($user);
        $entityManager->flush();

        // Effacer les données de session de l'utilisateur
        $session->clear();

        // Rediriger vers une page appropriée après la suppression du profil
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

