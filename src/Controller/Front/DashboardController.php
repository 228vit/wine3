<?php

namespace App\Controller\Front;

use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;
use Unisender\UniGoClient;

class DashboardController extends AbstractController
{

    /**
     * @Route("/testmail", name="front_testmail")
     */
    public function testMail(MailerInterface $mailer): Response
    {
        $senderEmail = $this->getParameter('mailer_sender_email');
        $client = new UniGoClient('6dta86diir7mm8f1emsr6jkyuwnskn5ycu151yia', 'go2.unisender.ru');

        $recipients = [
            [
                "email" => 'lexx@42point.com',
                "substitutions" => [
                    "to_name" => "Alex S."
                ],
            ],
        ];

        $body = [
            "html" => "<b>Test mail, {{to_name}}</b>",
            "plaintext" => "Hello, {{to_name}}",
            "amp" => "<!doctype html><html amp4email><head> <meta charset=\"utf-8\"><script async src=\"https://cdn.ampproject.org/v0.js\"></script> <style amp4email-boilerplate>body[visibility:hidden]</style></head><body> Hello, AMP4EMAIL world.</body></html>"
        ];

        // You can use email object can be used to prepare the message array.
        // But the email send method accepts an array, that can be composed without
        // SDK utils.
        $mail = new \Unisender\Model\Email($recipients, $body);
        $mail->setFromEmail($senderEmail);
        $mail->setSubject('[winedows] test letter');
        try {
            $response = $client->emails()->send($mail->toArray());
        } catch (\Exception $e) {
            dd($e);
        }

        dd($response);

        $email = (new Email())
            ->from($senderEmail)
            ->to('228vit@gmail.com')
            ->priority(Email::PRIORITY_HIGH)
            ->subject('[WINE] Mailer')
            ->text('Sending emails is fun again!')
        ;

        $mailer->send($email);

        return new Response('mail sent...');
    }

    /**
     * @Route("/cabinet", name="cabinet")
     */
    public function cabinet(EntityManagerInterface $em,
                              AuthenticationUtils $authenticationUtils,
                              ProductRepository $repository): Response
    {
        return $this->redirectToRoute('cabinet_product_index');
//        $user = $this->getUser();
//        $lastUsername = $authenticationUtils->getLastUsername();
//
//        return $this->render('front/cabinet/dashboard.html.twig', [
//            'user' => $user,
//            'last_username' => $lastUsername,
//        ]);
    }

    /**
     * @Route("/cabinet/dashboard", name="user_dashboard")
     */
    public function dashboard(EntityManagerInterface $em,
                              AuthenticationUtils $authenticationUtils,
                              ProductRepository $repository): Response
    {
        return $this->redirectToRoute('cabinet_product_index');

//        $user = $this->getUser();
//        $lastUsername = $authenticationUtils->getLastUsername();
//
//        return $this->render('front/cabinet/dashboard.html.twig', [
//            'user' => $user,
//            'last_username' => $lastUsername,
//        ]);
    }

}