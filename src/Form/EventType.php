<?php

namespace App\Form;

use App\Entity\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('dateTime')
            ->add('city')
            ->add('address')
            ->add('coordinates')
            ->add('description')
            ->add('slug')
            ->add('collage')
            ->add('announcePic')
            ->add('vendors')
            ->add('suppliers')
            ->add('organizer')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
