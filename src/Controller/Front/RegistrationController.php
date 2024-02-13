<?php

namespace App\Controller\Front;

use App\Entity\User;
use App\Form\ForgetPasswordType;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Service\FormErrorService;
use App\Utils\FormErrors;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use App\Serializer\FormErrorSerializer;

class RegistrationController extends AbstractController
{
    use FrontTraitController;

    private $emailVerifier;
    private $formErrorSerializer;
    private $formErrorService;

    public function __construct(EmailVerifier $emailVerifier,
                                FormErrorSerializer $formErrorSerializer,
                                FormErrorService $formErrorService)
    {
        $this->emailVerifier = $emailVerifier;
        $this->formErrorSerializer = $formErrorSerializer;
        $this->formErrorService = $formErrorService;
    }

    public function renderForm(): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);

        return $this->render('front/security/register_modal.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/register", name="app_register")
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $isAjax = $request->isXmlHttpRequest();
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            if ($isAjax) {
                $formErrors = $this->formErrorService->getErrors($form);
//                $formErrors = $this->formErrorSerializer->convertFormToArray($form);
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Возникли ошибки при отправке формы.',
                    'errors' => $formErrors,
                ], 200);
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $sendConfirmEmail = boolval($this->getParameter('mailer_send_confirm_email'));
            $senderEmail = $this->getParameter('mailer_sender_email');
            if (true === $sendConfirmEmail) {
                try {
                    // generate a signed url and email it to the user
                    $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                        (new TemplatedEmail())
                            // todo: move email to ENV!!!
                            ->from(new Address($senderEmail, 'Winedows Robot'))
                            ->to($user->getEmail())
                            ->subject('Please Confirm your Email')
                            ->htmlTemplate('front/email_templates/confirmation_email.html.twig')
                    );

                    if ($isAjax) {
                        return new JsonResponse([
                            'success' => true,
                            'message' => 'Вам отправлено письмо, следуйте инструкциям в нём.'
                        ]);
                    }

                    return $this->redirectToRoute('app_register_check_email', [
                        'uuid' => $user->getUuid(),
                    ]);
                } catch (\Exception $e) {
                    if ($isAjax) {
                        return new JsonResponse([
                            'success' => false,
                            'message' => 'Ошибка при отправке письма:' . $e->getMessage()
                        ], 500);
                    }
                    $this->addFlash('error', 'Возникла проблема при отправке письма. Обратитесь к администратору.');

                    return $this->redirectToRoute('app_login');
                }
            } else {
                if ($isAjax) {
                    return new JsonResponse([
                        'success' => true,
                        'message' => 'Вы успешно зарегистрированы, администратор в ближайшее время рассмотрит Вашу заявку.',
                        'redirect' => $this->generateUrl('app_login')
                    ]);
                }

                $this->addFlash('success', 'Вы успешно зарегистрированы, 
                    администратор в ближайшее время рассмотрит Вашу заявку.');
                $user->setIsVerified(true);

                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('front/registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/register/verify/email", name="app_verify_email")
     */
    public function verifyUserEmail(Request $request, UserRepository $userRepository): Response
    {
        $id = $request->get('id');

        if (null === $id) {
            $this->addFlash('error', 'Отсутствует параметр идентификации пользователя.');

            return $this->redirectToRoute('app_register');
        }

        $user = $userRepository->find($id);

        if (null === $user) {
            $this->addFlash('error', 'Неверный параметр идентификации пользователя.');

            return $this->redirectToRoute('app_register');
        }

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $exception->getReason());

            return $this->redirectToRoute('app_register');
        }

        // @TODO Change the redirect on success and handle or remove the flash message in your templates
        $this->addFlash('success', 'Your email address has been verified.');

        return $this->redirectToRoute('app_register_confirmed_email', [
            'uuid' => $user->getUuid(),
        ]);
    }

    /**
     * @Route("/register/check_email/{uuid}", name="app_register_check_email")
     */
    public function checkEmail(string $uuid, UserRepository $repository)
    {
        $user = $repository->getByUuid($uuid);

        return $this->render('front/registration/check_email.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @Route("/register/confirmed_email/{uuid}", name="app_register_confirmed_email")
     */
    public function confirmedEmail(string $uuid, UserRepository $repository)
    {
        $user = $repository->getByUuid($uuid);

        return $this->render('front/registration/confirmed_email.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @Route("/password/reset/sent/{uuid}", name="app_reset_password_sent")
     */
    public function resetPasswordSent(string $uuid, UserRepository $repository)
    {
        $user = $repository->getByUuid($uuid);

        return $this->render('front/registration/reset_password_sent.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @Route("/password/forget", name="app_forget_password")
     */
    public function forgetPassword(Request $request, UserRepository $userRepository)
    {
        $form = $this->createForm(ForgetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getNormData();
            $user = $userRepository->findOneBy([
                'email' => $email
            ]);

            if (null === $user) {
                $this->addFlash('error', 'Данный Email не найден.');

                return $this->render('front/registration/forget_password.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            try {
                $senderEmail = $this->getParameter('mailer_sender_email');

                // generate a signed url and email it to the user
                $this->emailVerifier->sendEmailConfirmation('app_reset_password', $user,
                    (new TemplatedEmail())
                        // todo: move email to ENV!!!
                        ->from(new Address($senderEmail, 'Winedows Robot'))
                        ->to($user->getEmail())
                        ->subject('Reset password')
                        ->htmlTemplate('front/email_templates/reset_password.html.twig')
                );

                return $this->redirectToRoute('app_reset_password_sent', [
                    'uuid' => $user->getUuid(),
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Возникла проблема при отправке письма. 
                    Обратитесь к администратору для подтверждения регистрации.');

                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('front/registration/forget_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/password/reset", name="app_reset_password")
     */
    public function passwordReset(Request $request,
                                  UserRepository $userRepository,
                                  UserPasswordEncoderInterface $passwordEncoder,
                                  MailerInterface $mailer): Response
    {
        // wtf? use UID!
        $id = $request->get('id');

        if (null === $id) {
            $this->addFlash('error', 'Отсутствует параметр идентификации пользователя.');

            return $this->redirectToRoute('app_register');
        }

        $user = $userRepository->find($id);

        if (null === $user) {
            $this->addFlash('error', 'Неверный параметр идентификации пользователя.');

            return $this->redirectToRoute('app_register');
        }

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $newPassword = rand('12345678', '99999999');
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $newPassword // $form->get('plainPassword')->getData()
                )
            );

            $senderEmail = $this->getParameter('mailer_sender_email');

            $email = (new TemplatedEmail())
                // todo: move email to ENV!!!
                ->from(new Address($senderEmail, 'Winedows Robot'))
                ->to($user->getEmail())
                ->subject('Your new password')
                ->htmlTemplate('front/email_templates/new_password.html.twig');
            $context = $email->getContext();
            $context['newPassword'] = $newPassword;

            $email->context($context);
            $mailer->send($email);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $exception->getReason());

            return $this->redirectToRoute('app_reset_password');
        }

        // @TODO Change the redirect on success and handle or remove the flash message in your templates
        $this->addFlash('success', 'На Ваш Email отправлен новый пароль, используйте его для авторизации.');

        return $this->redirectToRoute('app_login', [
            'uuid' => $user->getUuid(),
        ]);
    }
}
