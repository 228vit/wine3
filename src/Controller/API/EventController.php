<?php


namespace App\Controller\API;


use App\DTO\EventDTO;
use App\Entity\Event;
use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use OpenApi\Annotations as OA;
use App\DTO\EventListDTO;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Model;

class EventController extends AbstractController
{
    /**
     * Получение списка событий
     *
     * @Route("/api/events", name="api_event_index", methods={"GET"})
     * @OA\Get( tags={"Events"} )
     *
     * @OA\Response(response="200", description="Получение списка событий",
     *     @OA\JsonContent(
     *         type="array", @OA\Items(
     *            type="object",
     *            @OA\Property(property="slug", type="string"),
     *            @OA\Property(property="name", type="string"),
     *            @OA\Property(property="description", type="string"),
     *            @OA\Property(property="dateTime", type="string"),
     *            @OA\Property(property="city", type="string"),
     *            @OA\Property(property="address", type="string"),
     *            @OA\Property(property="orgenizer", type="string"),
     *         ),
     *     )
     * )
     *
     * @return Response
     */
    public function index(EventRepository $repository): Response
    {
        $res = [];
        /** @var Event $event */
        foreach ($repository->getLastTen() as $event) {
            $res[] = $event->asArray();
        }

        return new JsonResponse($res);
    }

    /**
     * Получение конкретного события
     *
     * @Route("/api/event/{slug}", name="api_event_view", methods={"GET"})
     * @OA\Get( tags={"Events"} )
     *
     * @OA\Response(response="200", description="конкретного события",
     *     @Model(type=EventDTO::class)
     * )
     *
     * @return Response
     */
    public function view(Event $event, string $webPicUploadsDir): Response
    {
        return new JsonResponse(new EventDTO($event, $webPicUploadsDir));
    }

}