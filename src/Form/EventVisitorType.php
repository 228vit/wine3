<?php

namespace App\Form;

use App\Entity\EventVisitor;
use App\Entity\Supplier;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class EventVisitorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('company')
            ->add('name')
            ->add('birthDate', null, [
                'years' => range(1970, date('Y')),
            ])
            ->add('phone')
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
            ->add('telegram')
            ->add('vk')
            ->add('isChecked', null, [
                'label' => 'Проверен администратором?'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => EventVisitor::class,
        ]);
    }
}
