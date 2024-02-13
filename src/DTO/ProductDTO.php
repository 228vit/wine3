<?php


namespace App\DTO;


use App\Entity\Category;
use App\Entity\Supplier;
use App\Entity\ImportLog;

final class ProductDTO
{
    public $productCode;
    public $name;
    public $color;
    public $type;
    public $wineColor;
    public $country;
    public $region;
    public $volume;
    public $alcohol;
    public $year;
    //  todo: remove it
    public $price;
    public $serveTemperature;
    public $vendorName;
    public $vendorUrl = '';
    public $grapeSort;
    public $foods;
    public $decantation = false;
    public $ratings;
    public $aging = '';
    public $packing = '';
    public $appellation = '';
    public $fermentation = '';
    public $agingType = '';

    //  todo: remove it
    /** @var Supplier|null  */
    public $supplier;

    //  todo: remove it
    /** @var ImportLog|null  */
    public $importLog;

    /** @var Category|null  */
    public $category;

    public function __construct(array $data, ?ImportLog $importLog = null)
    {
        $this->importLog = $importLog;
        $this->category = null !== $importLog ? $importLog->getCategory() : null;
        $this->supplier = null !== $importLog ? $importLog->getSupplier() : null;

        foreach ($data as $key => $value) {

            switch ($key) {
                case 'price':
                    $this->price = $this->getFloatValue($value);
                    break;
                case 'alcohol':
                    $this->alcohol = $this->getFloatValue($value);
                    break;
                case 'year':
                    $this->year = $this->getIntValue($value);
                    break;
                default:
                    $this->$key = $value;
            }
        }
    }

    private function getFloatValue(?string $value): ?float
    {

        // todo: convert empty to NULL
        return floatval($this->cleanNumber($value));
    }

    private function getIntValue(?string $value): ?int
    {

        return intval($this->cleanNumber($value));
    }

    private function cleanNumber(string $value): ?float
    {
        // убираем всё кроме цифр точек и зпт
        $value = preg_replace('/[^\d\.\,{1,}+]/u', '', $value);
        // убираем всё что ни цифра в конце - р. руб.
        $value = preg_replace('/[^0-9{1,}]$/u', '', $value);
        // 1,555.00 -> 1555.00
        // убираем все кроме цифр и точек
        $value = preg_replace('/[^0-9\,\.]/', '', $value);
        // разделитель дроби на точку ,0000 -> .0000
        $value = preg_replace('/,(\d+)$/', '.$1', $value);
        // могут остаться разделители групп цифр - запятые 1,000,000.00 -> 1000000.00
        $value = preg_replace('/,/', '', $value);

        return empty($value) ? 0 : $value;
    }

}