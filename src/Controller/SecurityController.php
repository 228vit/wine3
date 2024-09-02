<?php


namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SecurityController extends AbstractController
{
    /**
     * @Route("/ajax_login", name="ajax_login")
     */
    public function login(): Response
    {
        $user = $this->getUser();
        if (null === $user) {
            return new JsonResponse(['success' => false]);
        }

        return new JsonResponse([
            'success' => true,
            'redirect' => $this->generateUrl('cabinet_product_index'),
        ]);
    }
}