<?php


namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('price', [$this, 'formatPrice']),
            new TwigFilter('jsonAsString', [$this, 'jsonAsString']),
        ];
    }

    public function formatPrice($number, $decimals = 0, $decPoint = '.', $thousandsSep = ',', $currency = '$')
    {
        $price = number_format($number, $decimals, $decPoint, $thousandsSep);
        $price = $price . $currency;

        return $price;
    }

    public function jsonAsString($str, $glue = ', ', $onlyValues = false)
    {
        $res = json_decode($str, true);
        if (is_array($res)) {
            $items = [];
            foreach ($res as $index => $value) {
                if (true === $onlyValues) {
                    $items[] = $value;
                } else {
                    $items[] = sprintf('%s: %s', $index, $value);
                }
            }

            return implode($glue, $items);
        }

        return $str;
    }
}