<?php


namespace App\DTO;

use App\Entity\Event;
use App\Entity\EventPic;
use OpenApi\Annotations as OA;


final class EventDTO
{
    /** @OA\Property(type="string", maxLength=256) */
    public $name;
    /** @OA\Property(type="string", maxLength=32) */
    public $slug;
    /** @OA\Property(type="string", description="Main pic in header") */
    public $collage;
    /** @OA\Property(type="string", maxLength=32) */
    public $dateTime;
    /** @OA\Property(type="string", maxLength=100) */
    public $city;
    /** @OA\Property(type="string", maxLength=512) */
    public $address;
    /** @OA\Property(type="string", maxLength=512, nullable=true) */
    public $coordinates;
    /** @OA\Property(type="string", maxLength=512, nullable=true) */
    public $description;

    /** @OA\Property(type="array", @OA\Items(
     *      type="object",
     *      @OA\Property(property="pic", type="string"),
     * ))
     */
    public $eventPics;

    // todo: gallery etc

    public function __construct(Event $event, string $webPicUploadsDir = '')
    {
        $this->name = $event->getName();
        $this->slug = $event->getSlug();
        $this->dateTime = $event->getDateTime()->format('Y-m-d H:i:s');
        $this->city = $event->getCity();
        $this->address = $event->getAddress();
        $this->coordinates = $event->getCoordinates();
        $this->description = $event->getDescription();

        /** @var EventPic $eventPic */
        foreach ($event->getEventPics() as $eventPic) {
            if (empty($eventPic->getPic())) continue;

            $this->eventPics[] = $webPicUploadsDir . DIRECTORY_SEPARATOR . $eventPic->getPic();
        }
    }
}