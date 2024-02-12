<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Contract\Entity\TranslationInterface;
use Knp\DoctrineBehaviors\Model\Translatable\TranslationTrait;
/**
 * @ORM\Entity
 */
class ProductTranslation implements TranslationInterface
{
    use TranslationTrait;

/*
артикул
категория ?
производитель (назв, урл)
страна
регион
год пр-ва
сорт 1-8???
объем(л)
рейтинги (RP	WS	WE	ST	W&S	JR	GP	FM	B	Dec	Due	GR	GH	IWR	JH	JS	QUA-100	QUA-20	JO-100	JO-20	RFV	LE-20	LE-5	LM	LV-100	LV-3	BD	WA)
выпуск других годов (1-8)
гастр сочетания (1-8) (многояз)???
темп.подачи
декантация (да-нет)???

название (многояз)
тип - сл-плс-сух (многояз)
сорт виногр (многояз)
по Озу Кларку (многояз)
крепость
выдержка (нет в базе)
упаковка (нет в базе)
аппелясьон ru
тип ферментации
тип выдержки
 */

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $color;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $meta_keywords;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $meta_description;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $announce;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getMetaKeywords(): ?string
    {
        return $this->meta_keywords;
    }

    public function setMetaKeywords(string $meta_keywords): self
    {
        $this->meta_keywords = $meta_keywords;

        return $this;
    }

    public function getMetaDescription(): ?string
    {
        return $this->meta_description;
    }

    public function setMetaDescription(string $meta_description): self
    {
        $this->meta_description = $meta_description;

        return $this;
    }

    public function getAnnounce(): ?string
    {
        return $this->announce;
    }

    public function setAnnounce(?string $announce): self
    {
        $this->announce = $announce;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }
}
