<?php

namespace App\Controller\Front;

use App\Repository\ClientRepository;
use App\Repository\PageRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ClientsController extends AbstractController
{

    public const TOP_NEWS = 3;

    public function topClients(EntityManagerInterface $em,
                               ClientRepository $repository): Response
    {
        $topClient = $repository->getTopClients(self::TOP_NEWS);

        return $this->render('front/clients/top_clients.html.twig', [
            'topClients' => $topClient,
        ]);
    }

    public function onlyContent(EntityManagerInterface $em,
                                ClientRepository $repository, string $slug): Response
    {
        $news = $repository->findOneBy(['slug' => $slug]);

        if (null === $news) {
            return new Response('');
        }

        return new Response($news->getContent());
    }

}