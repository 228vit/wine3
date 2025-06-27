<?php


namespace App\Controller\API;

use App\Controller\API\CommonProfileController;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use App\DTO\UserProfileDTO;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends CommonProfileController
{
    /**
     * Получить профиль пользователя.
     *
     * @Route("/api/v2/user/profile", name="api_v2_get_user_profile",  methods={"GET"})
     * @OA\Get( tags={"User profile"} )
     * @OA\Parameter(name="Authorization", in="header", required=true, description="JWT access-token")
     * @OA\Response(response="200", description="User profile as JSON",
     *     @Model(type=UserProfileDTO::class)
     * )
     * @return Response
     */
    public function getUserProfile(): Response
    {
        return parent::getUserProfile();
    }

    /**
     * Обновить профиль пользователя.
     *
     * @Route("/api/v2/user/profile", name="api_v2_update_user_profile", methods={"POST"})
     * @OA\Parameter(name="Authorization", in="header", required=true, description="JWT access-token")
     *
     * @OA\Post(
     *     path="/api/v2/user/profile",
     *     tags={"User profile"},
     *     @OA\RequestBody(
     *         @OA\MediaType(mediaType="application/json",
     *           @OA\Schema(
     *            @OA\Property(property="name", type="string", example="John Doe"),
     *            @OA\Property(property="email", type="string", example="admin@mail.com"),
     *            @OA\Property(property="phone", type="string", example="79991112233"),
     *            @OA\Property(property="pic", type="string", example="base_64_encoded_pic"),
     *            @OA\Property(property="birthDate", type="string", example="1990-12-31"),
     *            @OA\Property(property="country", type="string", example="Midland"),
     *            @OA\Property(property="city", type="string", example="HobbyTown"),
     *            @OA\Property(property="address", type="string", example="Beerman ave"),
     *            @OA\Property(property="zip", type="string", example="123456"),
     *            @OA\Property(property="company", type="string"),
     *            @OA\Property(property="profession", type="string"),
     *           )
     *         )
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Профиль успешно обновлён",
     *    @OA\JsonContent(
     *        @OA\Property(property="status", type="string", example="success"),
     *    )
     * )
     * @OA\Response(
     *     response=400,
     *     description="Ошибка - неверные данные"
     * )
     * @return Response
     */
    public function updateUserProfile(Request $request): Response
    {
        return parent::updateUserProfile($request);
    }


}