<?php


namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SecurityController extends AbstractController
{
    /**
     * @Route("/new_login", name="new_login")
     */
    public function login(): Response
    {
        $user = $this->getUser();
        if (null === $user) {
            return new JsonResponse(['success' => false]);
        }

        return new JsonResponse([
            'success' => true,
            'redirect' => $this->generateUrl('user_dashboard'),
        ]);
    }
}