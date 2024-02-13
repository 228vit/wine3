<?php

namespace App\Controller\Front;

use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProfileController extends AbstractController
{

    /**
     * @Route("/cabinet/profile", name="user_profile")
     */
    public function profile(TranslatorInterface $translator,
                          EntityManagerInterface $em,
                          ProductRepository $repository): Response
    {
        $user = $this->getUser();

        return $this->render('front/profile/view.html.twig', [
            'user' => $user,
        ]);
    }

}