<?php

namespace App\Form;

use App\Entity\GrapeSort;
use App\Entity\ProductGrapeSort;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductGrapeSortSubformType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('grapeSort', EntityType::class, array(
                'class' => GrapeSort::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->addOrderBy('c.name', 'ASC');
                },
                'expanded' => false,
            ))
            ->add('value')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ProductGrapeSort::class,
        ]);
    }
}
