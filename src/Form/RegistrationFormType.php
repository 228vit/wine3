<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Не может быть пустым',
                    ]),
                    new Length([
                        'min' => 3,
                        'minMessage' => 'Не менее {{ limit }} символов',
                    ]),
                ]
            ])
            ->add('phone', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Введите номер телефона',
                    ]),
                    new Length([
                        'min' => 10,
                        'max' => 10,
                        'exactMessage' => 'Телефон должен быть 10 символов',
                    ]),
                ],
            ])
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
            ->add('birthDay', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('instagram')
            ->add('facebook')
            ->add('company')
//            ->add('agreeTerms', CheckboxType::class, [
//                'mapped' => false,
//                'label' => false,
//                'constraints' => [
//                    new IsTrue([
//                        'message' => 'Необходимо согласиться с уловиями пользования сервиса.',
//                    ]),
//                ],
//            ])
            ->add('plainPassword', PasswordType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Введите пароль',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Пароль должен быть как минимум {{ limit }} символов',
                        // max length allowed by Symfony for security reasons
                        'max' => 64,
                    ]),
                ],
            ])
        ;
    }

    public function getBlockPrefix() {
        return "registration";
    }
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
