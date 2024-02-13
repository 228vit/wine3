<?php

namespace App\Filter;

use App\Entity\Category;
use App\Entity\Country;
use App\Entity\Supplier;
use App\Entity\Vendor;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type\BooleanFilterType;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type\CheckboxFilterType;

class ImportCsvFilter extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setMethod('POST');
        $builder
            ->add('name', Filters\TextFilterType::class)
            ->add('supplier', EntityType::class, array(
                'class' => Supplier::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->addOrderBy('c.name', 'ASC');
                },
                'choice_label' => 'name',
                'required' => false,
            ))

            ->add('year', Filters\NumberFilterType::class, array(
                'required' => false,
            ))
            ->add('isComplete', BooleanFilterType::class)
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