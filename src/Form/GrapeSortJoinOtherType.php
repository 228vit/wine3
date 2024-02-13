<?php

namespace App\Form;

use App\Entity\GrapeSort;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GrapeSortJoinOtherType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('grapeSort', EntityType::class, array(
                'class' => GrapeSort::class,
                'expanded' => false,
            ))
        ;
    }

//    public function configureOptions(OptionsResolver $resolver)
//    {
//        $resolver->setDefaults([
//            'data_class' => GrapeSort::class,
//            'allow_extra_fields' => true,
//        ]);
//    }
}
