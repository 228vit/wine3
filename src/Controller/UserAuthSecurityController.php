<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class UserAuthSecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
         return $this->redirectToRoute('homepage');

        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

//        // get the login error if there is one
//        $error = $authenticationUtils->getLastAuthenticationError();
//        // last username entered by the user
//        $lastUsername = $authenticationUtils->getLastUsername();
//
//        return $this->render('front/security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * @Route("/login/ajax", name="ajax_login")
     */
    public function ajaxLogin(?User $user): Response
    {
        if (null === $user) {
             return $this->json([
                 'message' => 'missing credentials wtf',
             ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'user' => $user->getId(),
        ]);
    }
}
