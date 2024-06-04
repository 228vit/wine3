<?php

namespace App\Controller\Front;

use App\Entity\Event;
use App\Repository\EventRepository;
use App\Repository\PageRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;

class EventController extends AbstractController
{

    public const TOP_EVENTS = 3;


    /**
     * @Route("/event/{slug}", name="front_event_show")
     */
    public function showAction(Request $request, Event $event, EventRepository $repository)
    {
        $isAjax = $request->isXmlHttpRequest();
        $template = $isAjax ? 'front/event/show_ajax.html.twig' : 'front/event/show.html.twig';

        return $this->render($template, array(
            'row' => $event,
            'short_date' => $event->getDateTime()->format('d M'),
        ));
    }


    public function topEvent(EntityManagerInterface $em,
                               EventRepository $repository): Response
    {
        $topEvent = $repository->getTopEvent(self::TOP_EVENTS);

        return $this->render('front/event/top_event.html.twig', [
            'topEvent' => $topEvent,
        ]);
    }

    public function onlyContent(EntityManagerInterface $em,
                                EventRepository $repository, string $slug): Response
    {
        $event = $repository->findOneBy(['slug' => $slug]);

        if (null === $event) {
            return new Response('');
        }

        return new Response($event->getContent());
    }

}