<?php

namespace App\Controller\Front;

use App\Repository\PageRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class PageController extends AbstractController
{

    public function onlyContent(EntityManagerInterface $em,
                              PageRepository $repository, string $slug): Response
    {
        $page = $repository->findOneBy(['slug' => $slug]);

        if (null === $page) {
            return new Response('');
        }

        return new Response($page->getContent());
    }

}