<?php


namespace App\Service;

use \SimpleXMLElement;

final class CountryService
{
    private $xmlPath;

    public function __construct()
    {
        $this->xmlPath = __DIR__ . '/../../data/countries.xml';
    }

    public function getCountriesAlpha2(): array
    {
        $xml = simplexml_load_file($this->xmlPath);

        $result = [];
        foreach($xml as $country) {
            $result[$country->{alpha2}] =  $country->{name};
        }

        return $result;
    }
}