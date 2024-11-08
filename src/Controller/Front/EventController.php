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
     * @Route("/events/calendar", name="front_events_calendar")
     */
    public function calendar(Request $request, EventRepository $repository)
    {
        $firstDay = (new \DateTime('first day of this month'));
        $lastDay = (new \DateTime('last day of this month'));
        $firstDayNum = $firstDay->format('w');
        $currentMonth = $firstDay->format('m');
        $currentYear = $firstDay->format('Y');
        $firstDayNum = (int)$firstDayNum - 1;
        $firstWeekDay = $firstDay->modify('-' . $firstDayNum . ' days');

        $totalCalendarDays = (5*7) - 1;
        $calendar = [];
        $currentDay = clone $firstWeekDay;

        $thisMonthEvents = $repository->currentMonthEvents($firstDay, $lastDay);
        $events = [];
        /** @var Event $event */
        foreach ($thisMonthEvents as $event) {
            $events[$event->getDateTime()->format('d')] = $event;
        }

        for ($i = 0; $i <= $totalCalendarDays; $i++) {
            $dayNum = $currentDay->format('d');
            $calendar[] = [
                'day' => $dayNum,
//                'startTime' => $currentDay->format('H:i'),
                'weekDay' => $currentDay->format('w'),
                'month' => $currentDay->format('m'),
                'isCurrentMonth' => $currentMonth === $currentDay->format('m'),
                'isWeekend' => $currentDay->format('N') >= 6 ? true : false,
                'event' => isset($events[$dayNum]) ? $events[$dayNum] : false,
            ];

            $currentDay->modify('+ 1 day');
        }

        $years = [];
        $startYear = (int)date('Y') - 1;
        $endYear = $startYear + 3;
        for ($y = $startYear; $y <= $endYear; $y++) $years[] = $y;
        $months = [
            1 => 'январь', 2 => 'февраль', 3 => 'март', 4 => 'апрель', 5 => 'май', 6 => 'июнь',
            7 => 'июль', 8 => 'август', 9 => 'сентябрь',  10 => 'октябрь',  11 => 'ноябрь',  12 => 'декабрь',
        ];
        $isAjax = $request->isXmlHttpRequest();
        $template = $isAjax ? 'front/event/calendar.html.twig' : 'front/event/calendar.html.twig';

        return $this->render($template, array(
            'calendar' => $calendar,
            'currentMonth' => $currentMonth,
            'currentYear' => $currentYear,
            'years' => $years,
            'months' => $months,
//            'ruShortDate' => $shortDate,
//            'short_date' => $event->getDateTime()->format('d M'),
        ));
    }

    /**
     * @Route("/event/{slug}", name="front_event_show")
     */
    public function show(Request $request, Event $event, EventRepository $repository)
    {
        $isAjax = $request->isXmlHttpRequest();
        $template = $isAjax ? 'front/event/show_ajax.html.twig' : 'front/event/show.html.twig';
        $formatter = new \IntlDateFormatter(
            'ru_RU',
            \IntlDateFormatter::LONG,
            \IntlDateFormatter::LONG
        );
        $formatter->setPattern('d MMMM');
        $shortDate = ucwords($formatter->format($event->getDateTime()));

        return $this->render($template, array(
            'row' => $event,
            'ruShortDate' => $shortDate,
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