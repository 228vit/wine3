<?php

namespace App\Filter;

use App\Entity\Country;
use App\Entity\ImportLog;
use App\Entity\Supplier;
use App\Entity\Vendor;
use Doctrine\ORM\EntityRepository;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type\BooleanFilterType;
use Lexik\Bundle\FormFilterBundle\Filter\Query\QueryInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type as Filters;

class OfferFilter extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setMethod('POST');
        $builder
            ->add('product', BooleanFilterType::class, [
                'label' => 'Linked to product?',
                'apply_filter' => function (QueryInterface $filterQuery, $field, $value) {
                    $value = $value['value'];
                    if (null === $value) {
                        return null;
                    }

                    if (BooleanFilterType::VALUE_NO === $value) {
                        $filterQuery->getQueryBuilder()
                            ->andWhere(sprintf('%s IS NULL', $field));
                    } else {
                        $filterQuery->getQueryBuilder()
                            ->andWhere(sprintf('%s IS NOT NULL', $field));
                    }
                }
            ])
            ->add('import', EntityType::class, array(
                'class' => ImportLog::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->addOrderBy('c.updatedAt', 'DESC');
                },
                'choice_label' => 'summary',
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
                ],
                'label' => 'Цвет',
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
            ->add('year', Filters\NumberFilterType::class, array(
                'required' => false,
            ))
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