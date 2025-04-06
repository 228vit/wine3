<?php


namespace App\DTO;

use App\Entity\Event;
use OpenApi\Annotations as OA;


final class EventListDTO
{
    /** @OA\Property(type="string", maxLength=32) */
    public $slug;
    /** @OA\Property(type="string", maxLength=256) */
    public $name;
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

    public function __construct(Event $event)
    {
        $this->name = $event->getName();
        $this->slug = $event->getSlug();
        $this->dateTime = $event->getDateTime()->format('Y-m-d H:i:s');
        $this->city = $event->getCity();
        $this->address = $event->getAddress();
        $this->coordinates = $event->getCoordinates();
        $this->description = $event->getDescription();
    }

}