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

class ProductFilter extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setMethod('POST');
        $builder
            ->add('isEdited', BooleanFilterType::class)
            ->add('editor', EntityType::class, array(
                'class' => Admin::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->where('c.isEditor = true')
                        ->addOrderBy('c.id', 'ASC');
                },
                'choice_label' => 'email',
                'required' => false,
            ))
            ->add('name', TextType::class, ['required' => false])
            // todo: move types to product entity
            ->add('type', ChoiceType::class, array(
                'choices' => [
                    'сухое' => 'сухое',
                    'сладкое' => 'сладкое',
                ],
                'label' => 'Тип',
                'required' => false,
            ))
            // todo: move colors to product entity
            ->add('color', ChoiceType::class, array(
                'choices' => [
                    'красное' => 'красное',
                    'белое' => 'белое',
                    'розовое' => 'розовое',
                    'шампанское' => 'шампанское',
                ],
                'label' => 'Цвет',
                'required' => false,
            ))
            ->add('country', EntityType::class, array(
                'class' => Country::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->addOrderBy('c.name', 'ASC');
                },
                'choice_label' => 'name',
                'required' => false,
            ))
            ->add('vendor', EntityType::class, array(
                'class' => Vendor::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->addOrderBy('c.name', 'ASC');
                },
                'choice_label' => 'name',
                'required' => false,
                'expanded' => false,
            ))
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
//            ->add('volume', EntityType::class, array(
//                'class' => Product::class,
//                'query_builder' => function (EntityRepository $er) {
//                    return $er->createQueryBuilder('p')
//                        ->select('p.volume')
//                        ->where('p.volume > 0')
//                        ->groupBy('p.volume')
//                        ->addOrderBy('p.volume', 'ASC');
//                },
//                'choice_label' => 'name',
//                'required' => false,
//                'expanded' => false,
//            ))

            ->add('year', Filters\NumberFilterType::class, array(
                'required' => false,
                'attr' => [
                    'placeholder' => 'Год производства',
                ]
            ))
            ->add('isActive', BooleanFilterType::class)
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