<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\Supplier;
use App\Repository\SupplierRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
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
            ->add('collagePicFile', FileType::class, array(
                'label' => 'Collage PIC',
                'data_class' => null,
                'required' => false
            ))

            ->add('suppliers', EntityType::class, array(
                'class' => Supplier::class,
                'query_builder' => function (SupplierRepository $er) {
                    return $er->createQueryBuilder('v')
                        ->addOrderBy('v.name', 'ASC');
                },
                'choice_label' => 'name',
                'required' => false,
                'expanded' => true,
                'multiple' => true,
                'label' => 'Поставщики',
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
