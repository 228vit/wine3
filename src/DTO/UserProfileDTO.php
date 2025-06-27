<?php


namespace App\DTO;

use App\Entity\User;
use OpenApi\Annotations as OA;


final class UserProfileDTO
{
    /** @OA\Property(type="string", maxLength=256) */
    public $name;
    /** @OA\Property(type="string", maxLength=32) */
    public $email;
    /** @OA\Property(type="string") */
    public $phone;
    /** @OA\Property(type="string") */
    public $birthDay;
    /** @OA\Property(type="string", maxLength=32) */
    public $instagram;
    /** @OA\Property(type="string", maxLength=100) */
    public $facebook;
    /** @OA\Property(type="string", maxLength=512, nullable=true) */
    public $company;

//    /** @OA\Property(type="array", @OA\Items(
//     *      type="object",
//     *      @OA\Property(property="pic", type="string"),
//     * ))
//     */
//    public $userPics;

    public function __construct(User $user, string $webPicUploadsDir = '')
    {
        $this->name = $user->getName();
        $this->email = $user->getEmail();
        $this->birthDay = $user->getBirthDay()->format('Y-m-d');
        $this->instagram = $user->getInstagram();
        $this->facebook = $user->getFacebook();
        $this->company = $user->getCompany();
//        $this->description = $user->getDescription();

//        /** @var UserPic $userPic */
//        foreach ($user->getUserPics() as $userPic) {
//            if (empty($userPic->getPic())) continue;
//
//            $this->userPics[] = $webPicUploadsDir . DIRECTORY_SEPARATOR . $userPic->getPic();
//        }
    }
}