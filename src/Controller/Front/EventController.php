<?php

namespace App\Controller\Front;

use App\Entity\Event;
use App\Entity\EventOrganizer;
use App\Entity\EventVisitor;
use App\Repository\EventRepository;
use App\Repository\PageRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;

class EventController extends AbstractController
{
    use FrontTraitController;

    public const TOP_EVENTS = 3;


    /**
     * @Route("/events/calendar", name="front_events_calendar")
     */
    public function calendar(Request $request, EventRepository $repository)
    {
        $month = $request->query->get('month', null);
        $year = $request->query->get('year', null);
        $city = $request->query->get('city', null);
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
        $cities = [];
        $calendar = [];
        $currentDay = clone $firstWeekDay;

        // collect all cities
        $thisMonthEvents = $repository->currentMonthEvents($firstDay, $lastDay, null);
        /** @var Event $event */
        foreach ($thisMonthEvents as $event) {
            if ($event->getCity()) $cities[$event->getCity()] = trim($event->getCity());
        }

        // ?city? events
        $thisMonthEvents = $repository->currentMonthEvents($firstDay, $lastDay, $city);
        $events = [];
        /** @var Event $event */
        foreach ($thisMonthEvents as $event) {
            $events[$event->getDateTime()->format('md')] = $event;
            if ($event->getCity()) $cities[$event->getCity()] = trim($event->getCity());
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
            'currentCity' => $city,
            'cities' => array_filter($cities),
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
     * @Route("/event/view/{slug}", name="front_event_show")
     */
    public function show(Event $event, Request $request, EventRepository $repository)
    {
        $template = 'front/event/show.html.twig';
        $formatter = new \IntlDateFormatter(
            'ru_RU',
            \IntlDateFormatter::LONG,
            \IntlDateFormatter::LONG
        );
        $formatter->setPattern('d MMMM');
        $shortDate = ucwords($formatter->format($event->getDateTime()));

        return $this->render($template, array(
            'event' => $event,
            'ruShortDate' => $shortDate,
            'short_date' => $event->getDateTime()->format('d M'),
        ));
    }

    /**
     * @Route("/event/short_view/{slug}", name="front_event_short_view")
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

    /**
     * @Route("/event_organizer_new", name="front_event_organizer_new", methods={"POST"})
     */
    public function newOrganizer(Event $event, Request $request, MailerInterface $mailer)
    {
        // validate captcha
        $token = $request->request->get('smart-token', null);
        $ip = $request->getClientIp();

        $response = $this->validateCaptcha($token, $ip);
        if (is_array($response)) {
            return new JsonResponse(array_merge(['message' => 'Captcha fail'], $response), 400);
        }

        $companyName = $request->get('companyName', null);
        $person = $request->get('person', null);
        $jobTitle = $request->get('jobTitle', null);
        $phone = $request->get('phone', null);
        $email = $request->get('email', null);
        $description = $request->get('description', null);

        $eventOrg = (new EventOrganizer())
            ->setName($companyName)
            ->setPerson($person)
            ->setEmail($email)
            ->setJobTitle($jobTitle)
            ->setPhone($phone)
            ->setDescription($description)
        ;

        try {
            $this->em->persist($eventOrg);
            $this->em->flush();
        } catch (\Exception $e) {
            return new JsonResponse(['message' => $e->getMessage()], 400);
        }

        $adminEmail = $this->getParameter('admin_email');
        $senderEmail = $this->getParameter('mailer_sender_email');

        $email = (new TemplatedEmail())
            ->from($senderEmail)
            ->to($adminEmail)
            ->subject('[BigWine] Заявка на роль организатора мероприятия')
            ->htmlTemplate('front/email_templates/new_event_organizer.html.twig')
            ->context([
                'event' => $event,
            ])
        ;
        $mailer->send($email);

        return new JsonResponse(['message' => 'Success', 'mail' => 'sent']);
    }

    /**
     * @Route("/event_partner_new/{slug}", name="front_event_partner_new", methods={"POST"})
     */
    public function newEventPartner(Event $event, Request $request, MailerInterface $mailer)
    {
        // validate captcha
        $token = $request->request->get('smart-token', null);
        $ip = $request->getClientIp();

        $response = $this->validateCaptcha($token, $ip);
        if (is_array($response)) {
            return new JsonResponse(array_merge(['message' => 'Captcha fail'], $response), 400);
        }

        $companyName = $request->get('companyName', null);
        $person = $request->get('person', null);
        $jobTitle = $request->get('jobTitle', null);
        $phone = $request->get('phone', null);
        $email = $request->get('email', null);
        $description = $request->get('description', null);

        $eventOrg = (new EventOrganizer())
            ->setName($companyName)
            ->setPerson($person)
            ->setEmail($email)
            ->setJobTitle($jobTitle)
            ->setPhone($phone)
            ->setDescription($description)
        ;

        try {
            $this->em->persist($eventOrg);
            $this->em->flush();
        } catch (\Exception $e) {
            return new JsonResponse(['message' => $e->getMessage()], 400);
        }

        $adminEmail = $this->getParameter('admin_email');
        $senderEmail = $this->getParameter('mailer_sender_email');

        $email = (new TemplatedEmail())
            ->from($senderEmail)
            ->to($adminEmail)
            ->subject('[BigWine] Заявка на роль организатора мероприятия')
            ->htmlTemplate('front/email_templates/new_event_organizer.html.twig')
            ->context([
                'event' => $event,
            ])
        ;
        $mailer->send($email);

        return new JsonResponse(['message' => 'Success', 'mail' => 'sent']);
    }

    private function validateCaptcha(string $token, string $ip)
    {
        $key = $this->getParameter('yandex_smartcaptcha_key');
        $ch = curl_init("https://smartcaptcha.yandexcloud.net/validate");
        $args = [
            "secret" => $key,
            "token" => $token,
            "ip" => $ip, // Нужно передать IP-адрес пользователя.
            // Способ получения IP-адреса пользователя зависит от вашего прокси.
        ];
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($args));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode !== 200) {
//            echo "Allow access due to an error: code=$httpcode; message=$server_output\n";
            return ['code' => $httpcode, 'message' => $server_output];
        }

        $resp = json_decode($server_output);
        return $resp->status === "ok";
    }

    /**
     * @Route("/event_visitor_new/{slug}", name="front_event_visitor_new", methods={"POST"})
     */
    public function newVisitor(Event $event, Request $request, MailerInterface $mailer)
    {
        // validate captcha
        $token = $request->request->get('smart-token', null);
        $ip = $request->getClientIp();

        $response = $this->validateCaptcha($token, $ip);
        if (is_array($response)) {
            return new JsonResponse(array_merge(['message' => 'Captcha fail'], $response), 400);
        }

        // todo: validate request
        $company = $request->get('company', null);
        $name = $request->get('name', null);
        $phone = $request->get('phone', null);
        $email = $request->get('email', null);
        $telegram = $request->get('telegram', null);
        $vk = $request->get('vk', null);
        $instagram = $request->get('instagram', null);
        $facebook = $request->get('facebook', null);
        $linkedin = $request->get('linkedin', null);
        $year = $request->get('birthYear', null);
        $month = $request->get('birthMonth', null);
        try {
            $date = new \DateTimeImmutable("{$year}-{$month}-01");
        } catch (\Exception $e) {
            $date = null;
        }

        $eventVisitor = (new EventVisitor())
            ->setEvent($event)
            ->setName($name)
            ->setCompany($company)
            ->setEmail($email)
            ->setPhone($phone)
            ->setTelegram($telegram)
            ->setVk($vk)
            ->setInstagram($instagram)
            ->setFacebook($facebook)
            ->setLinkedin($linkedin)
            ->setBirthDate($date)
        ;

        try {
            $this->em->persist($eventVisitor);
            $this->em->flush();

        } catch (\Exception $e) {
            return new JsonResponse(['message' => $e->getMessage()], 400);
        }

        $senderEmail = $this->getParameter('mailer_sender_email');

        $email = (new TemplatedEmail())
            ->from($senderEmail)
            ->to($eventVisitor->getEmail())
            ->subject('[BigWine] Заявка на посещение мероприятия')
            ->htmlTemplate('front/email_templates/new_event_visitor.html.twig')
            ->context([
                'event' => $event,
            ])
        ;
        $mailer->send($email);

        return new JsonResponse(['message' => 'Success', 'mail' => 'sent']);
    }

}