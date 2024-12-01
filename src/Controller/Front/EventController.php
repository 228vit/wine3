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
        $month = $request->query->get('month', null);
        $year = $request->query->get('year', null);
        if (null === $month AND null === $year) {
            $firstDay = (new \DateTime('first day of this month'));
            $lastDay = (new \DateTime('last day of this month'));
            $firstDayNum = $firstDay->format('w');
            $currentMonth = $firstDay->format('m');
            $currentYear = $firstDay->format('Y');
            $firstDayNum = (int)$firstDayNum - 1;
            $firstWeekDay = $firstDay->modify('-' . $firstDayNum . ' days');
        }
        if (is_numeric($month) AND is_numeric($year)) {
            $firstDay = new \DateTime("first day of {$year}-{$month}");
            $lastDay = (new \DateTime("last day of {$year}-{$month}"));
            $firstDayNum = $firstDay->format('w');
            $currentMonth = $firstDay->format('m');
            $currentYear = $firstDay->format('Y');
            $firstDayNum = (int)$firstDayNum - 1;
            $firstWeekDay = $firstDay->modify('-' . $firstDayNum . ' days');

        }

        $totalCalendarDays = (5*7) - 1;
        $calendar = [];
        $currentDay = clone $firstWeekDay;

        $thisMonthEvents = $repository->currentMonthEvents($firstDay, $lastDay);
        $events = [];
        /** @var Event $event */
        foreach ($thisMonthEvents as $event) {
            $events[$event->getDateTime()->format('md')] = $event;
        }

//        dd($events);

        for ($i = 0; $i <= $totalCalendarDays; $i++) {
            $monthDayNum = $currentDay->format('md');
            if (isset($events[$monthDayNum])) {
//                dd($events[$monthDayNum]);
            }

            $dayNum = $currentDay->format('d');
            $calendar[] = [
                'day' => $dayNum,
//                'startTime' => $currentDay->format('H:i'),
                'weekDay' => $currentDay->format('w'),
                'month' => $currentDay->format('m'),
                'isCurrentMonth' => $currentMonth === $currentDay->format('m'),
                'isWeekend' => $currentDay->format('N') >= 6 ? true : false,
                'event' => isset($events[$monthDayNum]) ? $events[$monthDayNum] : false,
            ];

            $currentDay->modify('+ 1 day');
        }

//        dd($calendar);

        $years = [];
        $startYear = (int)date('Y') - 1;
        $endYear = $startYear + 3;
        for ($y = $startYear; $y <= $endYear; $y++) $years[] = $y;
        $months = [
            '01' => 'январь', '02' => 'февраль', '03' => 'март', '04' => 'апрель', '05' => 'май', '06' => 'июнь',
            '07' => 'июль', '08' => 'август', '09' => 'сентябрь',  '10' => 'октябрь',  '11' => 'ноябрь',  '12' => 'декабрь',
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

    /**
     * @Route("/event/{slug}/short_view", name="front_event_short_view")
     */
    public function renderShortView(Event $event)
    {
        $formatter = new \IntlDateFormatter(
            'ru_RU',
            \IntlDateFormatter::LONG,
            \IntlDateFormatter::LONG
        );

        $formatter->setPattern('d MMMM');
        $shortDate = ucwords($formatter->format($event->getDateTime()));
        $eventTime = $event->getDateTime()->format('H:i');

        return $this->render('front/event/showShortInfo.html.twig', [
            'event' => $event,
            'ruShortDate' => $shortDate,
            'eventTime' => $eventTime,
            'short_date' => $event->getDateTime()->format('d M'),
        ]);
    }

    public function thisMonthFirstEvent(int $currentYear, int $currentMonth,
                                        Request $request, EventRepository $repository)
    {
//        $isAjax = $request->isXmlHttpRequest();
//        $template = $isAjax ? 'front/event/show_ajax.html.twig' : 'front/event/show.html.twig';

        // get first event
        $dateStart = new \DateTime("first day of {$currentYear}-{$currentMonth}");
        $dateEnd = new \DateTime("last day of {$currentYear}-{$currentMonth}");

        /** @var Event $event */
        $event = $repository->currentMonthFirstEvent($dateStart, $dateEnd);

        if ($event) {
            return $this->renderShortView($event);
        }

        return new Response('');
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