<?php

namespace App\Controller\Front;

use App\Repository\NewsRepository;
use App\Repository\PageRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class NewsController extends AbstractController
{

    public const TOP_NEWS = 3;

    public function topNews(EntityManagerInterface $em,
                               NewsRepository $repository): Response
    {
        $topNews = $repository->getTopNews(self::TOP_NEWS);

        return $this->render('front/news/top_news.html.twig', [
            'topNews' => $topNews,
        ]);
    }

    public function onlyContent(EntityManagerInterface $em,
                                NewsRepository $repository, string $slug): Response
    {
        $news = $repository->findOneBy(['slug' => $slug]);

        if (null === $news) {
            return new Response('');
        }

        return new Response($news->getContent());
    }

}