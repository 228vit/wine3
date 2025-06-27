<?php


namespace App\Controller\API;

use App\DTO\UserProfileDTO;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\User;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

class CommonProfileController extends AbstractController
{
    /** @var SerializerInterface */
    private $serializer;
    private $fileUploader;
    private $em;
    private $uploads;
    private $validator;

    public function __construct(EntityManagerInterface $em,
                                FileUploader $fileUploader,
                                ValidatorInterface $validator,
                                string $webPicUploadsDir)
    {
        $this->em = $em;
        $this->uploads = $webPicUploadsDir;
        $this->validator = $validator;

        $this->fileUploader = $fileUploader;
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];

        $this->serializer = new Serializer($normalizers, $encoders);
    }

    /**
     * Получить профиль пользователя.
     *
     * @return Response
     */
    public function getUserProfile(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $dto = new UserProfileDTO($user);

        if (!$user) {
            return new JsonResponse(['message' => 'Bad credentials'], 401);
        }

        if ($user->getIsNew()) {
            $user->setIsNew(false);
            $this->em->persist($user);
            $this->em->flush();
        }

        return new JsonResponse(
            [
                'uuid' => $user->getUuid(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'phone' => $user->getPhone(),
                'pic' => $user->getPic() ? $this->uploads . '/' . $user->getPic() : null,
                'birthDate' => null !== $user->getBirthDate() ? $user->getBirthDate()->format('Y-m-d') : null,
                'country' => $user->getCountry(),
                'city' => $user->getCity(),
                'address' => $user->getAddress(),
                'zip' => $user->getZip(),
                'company' => $user->getCompany(),
                'profession' => $user->getProfession(),
                'createdAt' => $user->getCreatedAt()->format('Y-m-d'),
            ]
        );
    }

    /**
     * Удалить профиль пользователя.
     *
     * @return Response
     */
    public function delete(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

    }

    /**
     * Обновить профиль пользователя.
     *
     * @return Response
     */
    public function updateUserProfile(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $errors = [];

        $data = json_decode($request->getContent(), false);

        $name = isset($data->name) ? trim($data->name) : null;

        if (!empty($name)) {
            $violations = $this->validator->validate($name, [
                new Assert\NotBlank(),
            ]);
            if (0 !== count($violations)) {
                foreach ($violations as $violation) {
                    $errors['name'][] = $violation->getMessage();
                }
            } else {
                $user->setName(trim($name));
            }
        }

        $rawPhone = isset($data->phone) ? trim($data->phone) : null;
        $phone = preg_replace('/[^\+0-9]/', '', $rawPhone);

        if (!empty($phone)) {
            $user->setPhone($phone);
        }

        $email = isset($data->email) ? trim($data->email) : null;

        if (!empty($email)) {
            $violations = $this->validator->validate($email, [
                new Assert\NotBlank(),
                new Assert\Email(),
            ]);
            if (0 !== count($violations)) {
                foreach ($violations as $violation) {
                    $errors['email'][] = $violation->getMessage();
                }
            } else {
                $user->setEmail(trim($email));
            }
        }

        if (count($errors) > 0) {
            return new JsonResponse([
                'message' => 'Invalid submitted form data',
                'errors' => $errors,
            ], 400);
        }

        $picBody = isset($data->pic) ? trim($data->pic) : null;

        if (!empty($picBody)) {
            $user->setPic($this->base64_to_jpeg($user, $picBody));
        }

        $country = isset($data->country) ? trim($data->country) : null;
        $user->setCountry($country);
        
        $city = isset($data->city) ? trim($data->city) : null;
        $user->setCity($city);

        $address = isset($data->address) ? trim($data->address) : null;
        $user->setAddress($address);

        $zip = isset($data->zip) ? trim($data->zip) : null;
        $user->setZip($zip);

        $company = isset($data->company) ? trim($data->company) : null;
        $user->setCompany($company);
        
        $profession = isset($data->profession) ? trim($data->profession) : null;
        $user->setProfession($profession);

        if ($user->getIsNew()) {
            $user->setIsNew(false);
        }

        $this->em->persist($user);
        $this->em->flush();

        return $this->getUserProfile();
    }

    private function base64_to_jpeg(User $user, $base64_string): ?string
    {
        $fileName = sprintf('%s_%s.%s',
            'avatar',
            md5($user->getId() . time() . rand(11111, 99999)),
            'jpg'
        );

        $output_file =
            $this->getParameter('uploads_directory') . DIRECTORY_SEPARATOR .
            $this->getParameter('user_pics_subdirectory') . DIRECTORY_SEPARATOR . $fileName
        ;
        // open the output file for writing
        $ifp = fopen( $output_file, 'wb' );

        fwrite( $ifp, base64_decode( $base64_string ) );

        // clean up the file resource
        fclose( $ifp );

        return $this->getParameter('user_pics_subdirectory') . DIRECTORY_SEPARATOR . $fileName;
    }

}