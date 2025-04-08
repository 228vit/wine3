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

class DashboardController extends AbstractController
{

    /**
     * @Route("/testmail", name="front_testmail")
     */
    public function testMail(MailerInterface $mailer): Response
    {
        $senderEmail = $this->getParameter('mailer_sender_email');

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