<?php


namespace App\Controller\API;


use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AuthController extends AbstractController
{
    private $emailVerifier;
    private $mailer;
    private $em;
    private $httpClient;

    public function __construct(EmailVerifier $emailVerifier,
                                MailerInterface $mailer,
                                EntityManagerInterface $em,
                                HttpClientInterface $httpClient)
    {
        $this->emailVerifier = $emailVerifier;
        $this->mailer = $mailer;
        $this->em = $em;
        $this->httpClient = $httpClient;
    }

    /**
     * Регистрация/авторизация пользователя  в системе
     * @Route("/api/auth/request-magic-link", name="api_register", methods={"POST"})
     * @OA\Post(
     *      tags={"Auth"},
     *      @OA\RequestBody(
     *          @OA\MediaType(mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(property="email", type="string", example="user@gmail.com")
     *              )
     *          )
     *      )
     * )
     * @OA\Response(response="200", description="Пользователю на EMAIL отправлен код для входа в приложение",
     *     @OA\JsonContent(
     *         @OA\Property(property="email", type="string", example="user@email.com"),
     *         @OA\Property(property="message", type="string", example="Код и ссылка отправлены на email"),
     *         @OA\Property(property="accessCode", type="string", example="only for testers"),
     *     )
     * )
     */
    public function getCode(Request $request,
                             UserPasswordHasherInterface $passwordHasher,
                             UserRepository $userRepository)
    {
        $devServer = boolval($this->getParameter('is_dev_server'));

        $data = json_decode($request->getContent(), false);
        $email = $name = $phone = $password = isset($data->email) ? trim($data->email) : null;

        $validator = Validation::createValidator();
        $violations['email'] = $this->parseViolations($validator->validate($email, [
            new Email(),
            new NotBlank(),
        ]));

        foreach ($violations as $id => $violation) {
            if (0 === count($violation)) {
                unset($violations[$id]);
            }
        }

        if (count($violations) > 0) {
            return new JsonResponse($violations, 400);
        }

        $user = $userRepository->getByEmail($email);

        if (null === $user) {
            $user = new User();

            $user->setPassword($passwordHasher->hashPassword($user, $password));
            $user->setName($name);
            $user->setPhone($phone);
            $user->setEmail($email);
        }
        $user->setAccessCode(rand(100000, 999999));

        $this->em->persist($user);
        $this->em->flush();

        if ($devServer) {
            return new JsonResponse([
                'email' => $user->getEmail(),
                'message' => 'Код и ссылка отправлены на email',
                'accessCode' => $user->getAccessCode(),
            ]);
        }

        try {
            $this->sendAccessCode($user);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Ошибка при отправке письма:' . $e->getMessage()
            ], 500);
        }

        return new JsonResponse([
            'email' => $user->getEmail(),
            'message' => 'Код и ссылка отправлены на email',
        ]);
    }

    /**
     * Подтверждение регистрации/авторизации кодом из письма
     * @Route("/api/auth/verify-code", name="api_register_confirm", methods={"POST"})
     * @OA\Post(
     *      tags={"Auth"},
     *      @OA\RequestBody(
     *          @OA\MediaType(mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(property="email", type="string", example="user@gmail.com"),
     *                  @OA\Property(property="access_code", type="integer", example=123456)
     *              )
     *          )
     *      )
     * )
     * @OA\Response(response="200", description="Подтверждение регистрации/авторизации пользователя в системе",
     *     @OA\JsonContent(
     *         @OA\Property(property="access_token", type="string", example="long hash string"),
     *         @OA\Property(property="refresh_token", type="string", example="hash"),
     *         @OA\Property(property="user", type="object",
     *              @OA\Property(property="uuid", type="string"),
     *              @OA\Property(property="name", type="string"),
     *              @OA\Property(property="email", type="string"),
     *         ),
     *     )
     * )
     * @OA\Response(response="400", description="Не верные данные авторизации",
     *     @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Wrong auth data"),
     *     )
     * )
     */
    public function confirm(Request $request,
                            UserRepository $userRepository)
    {
        $data = json_decode($request->getContent(), false);
        $email = isset($data->email) ? trim($data->email) : null;
        $accessCode = isset($data->accessCode) ? intval(trim($data->accessCode)) : null;

        if (empty($accessCode) OR empty($email)) {
            return new JsonResponse([
                'message' => 'Bad request',
            ], 400);
        }

        // todo: уменьшить кол-во проверок
        $user = $userRepository->getByEmailNotBanned($email);

        if (null === $user) {
            return new JsonResponse(['message' => 'Wrong email'], 400);
        }
        if (null !== $accessCode AND (int)$accessCode !== (int)$user->getAccessCode()) {
            return new JsonResponse([
                'message' => 'Wrong access code',
            ], 400);
        }

        try {
            $tokenExpired = $this->getParameter('jwt_token_expiration');
        } catch (ServiceNotFoundException $e) {
            $tokenExpired = "600 minutes";
        }

        $payload = [
            "user" => $user->getEmail(),
            "exp"  => (new \DateTime())->modify("+".$tokenExpired)->getTimestamp(),
        ];

        $jwt = JWT::encode($payload, $this->getParameter('jwt_secret'), 'HS256');
        $refreshToken = md5(rand(1111, 9999) . time() . $user->getEmail() . rand(100000, 999999));

        $user->setAccessCode(null);
        $user->setRefreshToken($refreshToken);
        $this->em->persist($user);
        $this->em->flush();

        return $this->json([
            'access_token' => sprintf('%s', $jwt),
            'refresh_token' => sprintf('%s', $refreshToken),
            'user' => [
                'uuid' => $user->getUuid()->toString(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
            ],
        ]);
    }

    /**
     * Refresh user JWT token
     * @Route("/api/refresh_token", name="api_refresh_token", methods={"POST"})
     * @OA\Post(
     *      tags={"Auth"},
     *      @OA\RequestBody(
     *          @OA\MediaType(mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(property="refreshToken", type="string", example="hash"),
     *              )
     *          )
     *      )
     * )
     * @OA\Response(response="200", description="Обновление токена JWT",
     *     @OA\JsonContent(
     *         @OA\Property(property="token", type="string", example="long hash string"),
     *         @OA\Property(property="refreshToken", type="string", example="hash string"),
     *     )
     * )
     * @OA\Response(response="400", description="Неверный запрос",
     *     @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Wrong token"),
     *     )
     * )
     */
    public function refresh(Request $request,
                            UserRepository $userRepository)
    {
        $data = json_decode($request->getContent(), false);
        $refreshToken = isset($data->refreshToken) ? trim($data->refreshToken) : null;

        if (empty($refreshToken)) {
            return new JsonResponse(['message' => 'Wrong token supported'], 400);
        }

        $user = $userRepository->findOneBy(['refreshToken' => $refreshToken]);

        if (null === $user) {
            return new JsonResponse(['message' => 'Wrong token supported'], 400);
        }

        try {
            $tokenExpired = $this->getParameter('jwt_token_expiration');
        } catch (ServiceNotFoundException $e) {
            $tokenExpired = "600 minutes";
        }

        $payload = [
            "user" => $user->getEmail(),
            "exp"  => (new \DateTime())->modify("+".$tokenExpired)->getTimestamp(),
        ];

        $jwt = JWT::encode($payload, $this->getParameter('jwt_secret'), 'HS256');
        $refreshToken = md5(rand(1111, 9999) . time() . $user->getEmail() . rand(100000, 999999));

        $user->setRefreshToken($refreshToken);
        $this->em->persist($user);
        $this->em->flush();

        return $this->json([
            'token' => sprintf('%s', $jwt),
            'refreshToken' => sprintf('%s', $refreshToken),
        ]);
    }


    /**
     * Пометить авторизованного пользователя на удаление
     * @Route("/api/user", name="api_user_delete", methods={"DELETE"})
     * @OA\Delete(
     *      tags={"Auth"},
     * )
     * @OA\Response(response="200", description="Пользователю на EMAIL отправлено с подтверждением об удалении",
     *     @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="success"),
     *     )
     * )
     * @OA\Response(response="400", description="Bad request",
     *     @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="Bad request"),
     *     )
     * )
     * @OA\Response(response="401", description="Unauthenticated",
     *     @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="User is not authenticated"),
     *     )
     * )
     */
    public function delete(Request $request,
                           UserRepository $userRepository,
                           EventDispatcherInterface $eventDispatcher)
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Unauthenticated'], 401);
        }

        if (Request::METHOD_DELETE !== $request->getMethod()) {
            return new JsonResponse(null, 400);
        }

        $email = $user->getEmail();

        // if already deleted
        $deletedEmail = "[deleted]".$email;
        while(true) {
            $deletedUser = $userRepository->findOneBy(['email' => $deletedEmail]);
            if (null === $deletedUser) {
                break;
            }
            $deletedEmail = "[deleted]".$deletedEmail;
        }

        $user->setIsDeleted(true)
            ->setIsActive(false)
            ->setIsVerified(false)
            ->setAutoLogin(false)
            ->setDeletedAt(new \DateTimeImmutable('now'))
            ->setEmail($deletedEmail)
            ->setAccessCode(null)
            ->setRefreshToken(null)
        ;

        $this->em->persist($user);
        $this->em->flush();

        $this->sendDeleteMessage($email);

        $request->getSession()->invalidate();

        return new JsonResponse(['message' => 'success']);
    }

    private function sendDeleteMessage(string $email)
    {
        try {
            $senderEmail = $this->getParameter('mailer_sender_email');

            $message = (new TemplatedEmail())
                ->from($senderEmail)
                ->to($email)
                ->subject('[Winedows] delete account notification')
                ->htmlTemplate('front/email_templates/delete_account_notification.html.twig')
            ;

            $this->mailer->send($message);
        } catch (\Exception $e) {

        }
    }

    private function sendAccessCode(User $user)
    {
        $senderEmail = $this->getParameter('mailer_sender_email');

        $email = (new TemplatedEmail())
            ->from($senderEmail)
            ->to($user->getEmail())
            ->subject('[Winedows] access code')
            ->htmlTemplate('front/email_templates/registration_access_code.html.twig')
            ->context([
                'accessCode' => $user->getAccessCode(),
            ])
        ;

        $this->mailer->send($email);
    }

    private function parseViolations(ConstraintViolationListInterface $violations) {
        $res = [];
        if (0 !== count($violations)) {
            // there are errors, now you can show them
            foreach ($violations as $violation) {
                $res[] = $violation->getMessage();
            }
        }

        return $res;
    }

}


//    /**
//     * Авторизация с токеном от Google, необходимо предоставить один из токенов
//     * @Route("/api/google/login", name="api_google_login", methods={"POST"})
//     * @OA\Post(
//     *      tags={"Auth"},
//     *      @OA\RequestBody(
//     *          @OA\MediaType(mediaType="application/json",
//     *              @OA\Schema(
//     *                  @OA\Property(property="googleIdToken", type="string"),
//     *                  @OA\Property(property="googleAccessToken", type="string"),
//     *              )
//     *          )
//     *      )
//     * )
//     * @OA\Response(response="200", description="Авторизация с токеном от Google, необходимо предоставить один из токенов",
//     *     @OA\JsonContent(
//     *         @OA\Property(property="token", type="string", example="long hash string"),
//     *         @OA\Property(property="refresh_token", type="string", example="hash string"),
//     *     )
//     * )
//     * @OA\Response(response="400", description="Неверный формат запроса",
//     *     @OA\JsonContent(
//     *         @OA\Property(property="message", type="string"),
//     *     )
//     * )
//     */
//    public function googleLogin(Request $request,
//                                 UserRepository $userRepository)
//    {
//        $data = json_decode($request->getContent(), false);
//
//        $googleIdToken = isset($data->googleIdToken) ? trim($data->googleIdToken) : null;
//        $googleAccessToken = isset($data->googleAccessToken) ? trim($data->googleAccessToken) : null;
//
//        if (empty($googleAccessToken) AND  empty($googleIdToken)) {
//            return new JsonResponse(['message' => 'Empty token'], 400);
//        }
//
//        try {
//            $url = !empty($googleIdToken)
//                ? "https://oauth2.googleapis.com/tokeninfo?id_token=$googleIdToken"
//                : "https://oauth2.googleapis.com/tokeninfo?access_token=$googleAccessToken"
//            ;
//            /** @var ResponseInterface $response */
//            $response = $this->httpClient->request(
//                'GET',
//                $url
//            );
//
//            $content = $response->toArray(false);
//        } catch (\Throwable $e) {
//            return new JsonResponse(['message' => $e->getMessage()], 400);
//        }
//
//        // check with Google if user is valid
//        $email = isset($content['email']) ? $content['email'] : null;
//        $name = isset($content['name']) ? $content['name'] : null;
//        $googleId = isset($content['sub']) ? $content['sub'] : null;
//        $emailVerified = isset($content['email_verified']) ? $content['email_verified'] : false;
//        $emailVerified = "true" === $emailVerified ? true : false;
//
//        if (false === $emailVerified) {
//            return new JsonResponse(['message' => 'Not verified Email'], 400);
//        }
//
//        $user = $userRepository->getByEmail($email);
//
//        if (null === $user) {
//            $name = !empty($name) ? $name : $email;
//            $user = new User();
//            $user->setIsVerified(true);
//            $user->setIsFinishedQuiz(true);
//            $user->setName($name);
//            $user->setEmail($email);
//        }
//
//        try {
//            $tokenExpired = $this->getParameter('jwt_token_expiration');
//        } catch (ServiceNotFoundException $e) {
//            $tokenExpired = "600 minutes";
//        }
//
//        $payload = [
//            "user" => $user->getEmail(),
//            "exp"  => (new \DateTime())->modify("+".$tokenExpired)->getTimestamp(),
//        ];
//
//        $jwt = JWT::encode($payload, $this->getParameter('jwt_secret'), 'HS256');
//        $refreshToken = md5(rand(1111, 9999) . time() . $user->getEmail() . rand(100000, 999999));
//
//        $user->setGoogleId($googleId);
//        $user->setRefreshToken($refreshToken);
//        $this->em->persist($user);
//        $this->em->flush();
//
//        return $this->json([
//            'token' => sprintf('%s', $jwt),
//            'refreshToken' => sprintf('%s', $refreshToken),
//        ]);
//    }
