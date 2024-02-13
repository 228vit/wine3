<?php

namespace App\Filter\Front;

use App\Entity\Category;
use App\Entity\Country;
use App\Entity\Product;
use App\Entity\Supplier;
use App\Entity\Vendor;
use App\Entity\WineColor;
use App\Entity\WineSugar;
use App\Service\ProductDataService;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type\BooleanFilterType;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type\CheckboxFilterType;

class FrontProductFilter extends AbstractType
{
    private $bottleVolumes = [];

    public function __construct(ProductDataService $service)
    {
        $this->bottleVolumes = array_combine(
            array_values($service->getBottleVolumes()),
            array_values($service->getBottleVolumes())
        );
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setMethod('POST');
        $builder
            ->add('name', HiddenType::class, ['required' => false])
            // todo: move types to product entity
            ->add('wineSugar', EntityType::class, array(
                'class' => WineSugar::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c');
                },
                'choice_label' => 'name',
                'label' => "Сахар",
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ))
            ->add('wineColor', EntityType::class, array(
                'class' => WineColor::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c');
                },
                'choice_label' => 'name',
                'label' => "Цвет",
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ))
            ->add('country', EntityType::class, array(
                'class' => Country::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->addOrderBy('c.name', 'ASC');
                },
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
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
                'expanded' => true,
                'multiple' => true,
            ))
            ->add('supplier', EntityType::class, array(
                'class' => Supplier::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->addOrderBy('c.name', 'ASC');
                },
                'choice_label' => 'name',
                'required' => false,
                'expanded' => true,
                'multiple' => true,
            ))

            ->add('price_from', Filters\NumberFilterType::class, array(
                'required' => false,
                'attr' => [
                    'placeholder' => 'От',
                    'size' => 4
                ]
            ))

            ->add('price_to', Filters\NumberFilterType::class, array(
                'required' => false,
                'attr' => [
                    'placeholder' => 'До',
                    'size' => 4
                ]
            ))

            ->add('year', Filters\NumberFilterType::class, array(
                'required' => false,
                'attr' => [
                    'placeholder' => 'Год производства',
                ]
            ))

            ->add('worldPart', Filters\ChoiceFilterType::class, array(
                'required' => false,
                'choices' => [
                    'Новый свет' => 'new_world',
                    'Старый свет' => 'old_world',
                ],
                'expanded' => true,
                'multiple' => true,
                'attr' => [
                    'placeholder' => 'Часть света',
                ]
            ))
            ->add('volume', Filters\ChoiceFilterType::class, array(
                'required' => false,
                'choices' => $this->bottleVolumes,
                'expanded' => true,
                'multiple' => true,
            ))
        ;
    }

    public function getBlockPrefix()
    {
        return 'product_filter';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection'   => false,
            'validation_groups' => array('filtering') // avoid NotBlank() constraint-related message
        ));
    }

}