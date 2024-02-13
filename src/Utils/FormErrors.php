<?php


namespace App\Utils;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;

class FormErrors
{
    public static function asArray(FormInterface $form): array
    {
        $result = [];
        foreach ($form->getErrors(true, false) as $formError) {
            if ($formError instanceof FormError) {
                $result[$formError->getOrigin()->getName()] = $formError->getMessage();
            // todo: понять правильно ли это?
            } elseif ($formError instanceof FormErrorIterator) {
                $result[$formError->getForm()->getName()] = self::asArray($formError->getForm());
            }
        }
        return $result;
    }

    public static function toString(FormInterface $form): string
    {
        return print_r(self::asArray($form), true);
    }
}