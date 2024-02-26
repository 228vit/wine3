<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\Vendor;
use App\Repository\VendorRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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
            ->add('address')
            ->add('coordinates')
            ->add('description')
            ->add('vendors', EntityType::class, array(
                'class' => Vendor::class,
                'query_builder' => function (VendorRepository $er) {
                    return $er->createQueryBuilder('v')
                        ->addOrderBy('v.name', 'ASC');
                },
                'choice_label' => 'name',
                'required' => false,
                'expanded' => true,
                'multiple' => true,
                'label' => 'Партнёры',
            ))

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
