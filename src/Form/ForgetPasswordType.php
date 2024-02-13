<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;

class ForgetPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Не может быть пустым',
                    ]),
                    new Email([
                        'message' => 'Некорректный Email',
                    ]),
                ],
            ])
        ;
    }

}
