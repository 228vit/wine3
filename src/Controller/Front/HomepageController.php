<?php

namespace App\Controller\Front;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

class HomepageController extends AbstractController
{

    /**
     * @Route("/", name="homepage")
     */
    public function index(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('front/homepage/index.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }
    /**
     * @Route("/yandex_captcha", name="yandex_captcha")
     */
    public function yandexCaptcha(Request $request): Response
    {
        $secretKey = $this->getParameter('yandex_captcha_secret_key');
        $token = $request->get('captchaToken', null);
        $ip = $request->getClientIp();
        $ch = curl_init("https://smartcaptcha.yandexcloud.net/validate");

        $args = [
            "secret" => $secretKey,
            "token" => $token,
            "ip" => $ip, // Нужно передать IP-адрес пользователя.
        ];
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($args));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode !== 200) {
            return new JsonResponse(['message' => "Allow access due to an error: code=$httpcode; message=$server_output", 400]);
        }

        $resp = json_decode($server_output);
        if ($resp->status === "ok") {
            return new JsonRequest();
        }

        return new JsonRequest($resp, 400);
    }

    /**
     * @Route("/translate", name="old_homepage")
     */
    public function trans(TranslatorInterface $translator,
                          EntityManagerInterface $em,
                          ProductRepository $repository): Response
    {
//        $products = $repository->findAll();
//        die('total products: ' . count($products));
/*
SELECT o.name, o.price, cff.name, cff.title, cffv.text_val, cffv.int_val FROM `catalog_objects` o
INNER JOIN catalog_filtered_fields_values cffv ON cffv.object_id = o.id
INNER JOIN catalog_filtered_fields cff ON cffv.field_id = cff.id
WHERE o.id = 245;
*/
        $sql = '
            SELECT * FROM catalog_objects LIMIT 10 OFFSET 0
        ';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAllAssociative();

        $langs = [
            19 => 'ru',
            20 => 'en',
            21 => 'fr',
        ];

        print_r($data); die();

        $product = new Product();
        $product->setUrl('http://')
            ->setPrice(1000)
            ->setIsActive(false)
        ;

        $product->translate('en')
            ->setName('New vine')
            ->setAnnounce('New vine announce')
            ->setDescription('New vine description')
            ->setMetaKeywords('New vine description')
            ->setMetaDescription('New vine description')
        ;
        $product->translate('ru')
            ->setName('Новое вино')
            ->setAnnounce('Новое вино')
            ->setDescription('Новое вино')
            ->setMetaKeywords('Новое вино')
            ->setMetaDescription('Новое вино')
        ;

        $product->mergeNewTranslations();
        $em->persist($product);
        $em->flush();

        $translated = $translator->trans('Symfony is great');

        return $this->render('homepage/index.html.twig', [
            'controller_name' => 'HomepageController',
            'text' => $translated,
        ]);
    }
}
