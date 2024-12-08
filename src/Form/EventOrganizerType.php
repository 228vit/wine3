<?php

namespace App\Form;

use App\Entity\EventOrganizer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventOrganizerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'Название компании'
            ])
            ->add('url')
//            ->add('pic')
            ->add('person', null, [
                'label' => 'ФИО представителя'
            ])
            ->add('jobTitle', null, [
                'label' => 'Должность представителя'
            ])
            ->add('phone', null, [
                'label' => 'Тел. представителя'
            ])
            ->add('email', null, [
                'label' => 'E-Mail представителя'
            ])
            ->add('isChecked', null, [
                'label' => 'Проверен администратором'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EventOrganizer::class,
        ]);
    }
}
