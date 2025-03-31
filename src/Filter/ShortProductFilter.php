<?php

namespace App\Filter;

use App\Entity\Admin;
use App\Entity\Country;
use App\Entity\Product;
use App\Entity\Supplier;
use App\Entity\Vendor;
use App\Entity\WineSugar;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type\BooleanFilterType;

class ShortProductFilter extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setMethod('POST');
        $builder
            ->add('name', TextType::class, ['required' => false])

            ->add('supplier', EntityType::class, array(
                'class' => Supplier::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->addOrderBy('c.name', 'ASC');
                },
                'choice_label' => 'name',
                'required' => false,
                'expanded' => false,
            ))
            ->add('wineSugar', EntityType::class, array(
                'class' => WineSugar::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->addOrderBy('c.name', 'ASC');
                },
                'choice_label' => 'name',
                'required' => false,
                'expanded' => false,
            ))

            ->add('year', Filters\NumberFilterType::class, array(
                'required' => false,
                'attr' => [
                    'placeholder' => 'Год производства',
                ]
            ))
            ->add('isActive', BooleanFilterType::class)
            ->add('isEmptyPic', BooleanFilterType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'With empty pics'
            ])
        ;
    }

    public function getBlockPrefix()
    {
        return 'item_filter';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection'   => false,
            'validation_groups' => array('filtering') // avoid NotBlank() constraint-related message
        ));
    }

}