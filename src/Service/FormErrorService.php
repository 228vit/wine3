<?php


namespace App\Service;

use Symfony\Component\Form\FormInterface;

interface FormErrorServiceInterface
{
    public function getErrors(FormInterface $form);
}

class FormErrorService implements FormErrorServiceInterface
{
    public function getErrors(FormInterface $form)
    {
        $errors = array();

        // This part gets global form errors (like csrf token error)
        foreach ($form->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }

        // This part gets form field errors
        foreach ($form->all() as $child) {
            if (! $child->isValid()) {
                $options = $child->getConfig()->getOptions();
                $field = $options['label'] ? $options['label'] : ucwords($child->getName());
                // Implode because there can be more than one field errors
                $errors[] = [
                    'name' => strtolower($field),
                    'message' => implode('; ', $this->getErrors($child))
                ];
            }
        }

        return $errors;
    }
}