<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
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
            ->add('phone')
//            ->add('roles')
//            ->add('password')
            ->add('isVerified')
            ->add('isActive')
//            ->add('createdAt')
//            ->add('updatedAt')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
