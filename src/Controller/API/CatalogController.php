<?php


namespace App\Controller\API;


use App\Entity\Event;
use App\Entity\Product;
use App\Repository\EventRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Model;

class CatalogController extends AbstractController
{

    /**
     * Получение страницы товаров из каталога
     *
     * @Route("/api/products", name="api_product_index", methods={"POST"})
     * @OA\Parameter(name="Authorization", in="header", required=true, description="JWT access-token")
     * @OA\Post( tags={"Products"},
     *     @OA\RequestBody(
     *         @OA\MediaType(mediaType="application/json",
     *           @OA\Schema(
     *              @OA\Property(property="search", type="string", example="Search string"),
     *              @OA\Property(property="sugar", type="string", example="sweet | semi-sweet | semi-dry | dry | extra-sry"),
     *              @OA\Property(property="wine_color", type="string", example="red | pink | white"),
     *              @OA\Property(property="country", type="string", example="France"),
     *              @OA\Property(property="region", type="string", example="Bordeaux"),
     *              @OA\Property(property="year", type="string", example="1990"),
     *              @OA\Property(property="page", type="integer", example="1", default="1", description="Страница (1 по умолчанию)"),
     *              @OA\Property(property="rows", type="integer", example="10", default="20", description="Количество записей на странице (20 по умолчанию)"),
     *              @OA\Property(property="sort_by", type="string", example="date | id | name", default="date", description="Сортировка по полю"),
     *              @OA\Property(property="order", type="string", example="asc | desc", default="desc", description="Сортировка по возрастанию/убыванию"),
     *           )
     *         )
     *     )
     * )
     * @OA\Response(response="200", description="Получение страницы товаров из каталога",
     *     @OA\JsonContent(
     *         type="array", @OA\Items(
     *            type="object",
     *            @OA\Property(property="id", type="string"),
     *            @OA\Property(property="name", type="string"),
     *            @OA\Property(property="pic", type="string"),
     *            @OA\Property(property="wine_color", type="string"),
     *            @OA\Property(property="wine_sugar", type="string"),
     *            @OA\Property(property="description", type="string"),
     *            @OA\Property(property="country", type="string"),
     *            @OA\Property(property="year", type="string"),
     *            @OA\Property(property="vendor", type="string"),
     *         ),
     *     )
     * )
     *
     * @return Response
     */
    public function index(ProductRepository $repository, string $webPicUploadsDir): Response
    {
        $res = [];
        /** @var Product $product */
        foreach ($repository->getTopTen(20) as $product) {
            $res[] = $product->asArray($webPicUploadsDir);
        }

        return new JsonResponse($res);
    }
}