<?php

namespace App\Form;

use App\Entity\Offer;
use App\Entity\Product;
use App\Entity\Supplier;
use App\Entity\Vendor;
use Doctrine\ORM\EntityRepository;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Sirian\SuggestBundle\Form\Type\SuggestType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class OfferType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
//            ->add('product', EntityType::class, array(
//                'class' => Product::class,
//                'query_builder' => function (EntityRepository $er) {
//                    return $er->createQueryBuilder('c')
//                        ->addOrderBy('c.name', 'ASC');
//                },
//                'choice_label' => 'name',
//                'required' => false,
//                'expanded' => false,
//            ))
            ->add('product', SuggestType::class, [
                'suggester' => 'product',
                'required' => false,
            ])
            ->add('supplier', EntityType::class, array(
                'class' => Supplier::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->addOrderBy('c.name', 'ASC');
                },
                'label' => 'Поставщик',
                'choice_label' => 'name',
                'required' => false,
                'expanded' => false,
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
            ->add('category', EntityType::class, array(
                'class' => 'App:Category',
                'expanded' => false,
            ))
            ->add('country', EntityType::class, array(
                'class' => 'App:Country',
                'expanded' => false,
            ))
            ->add('region', EntityType::class, array(
                'class' => 'App:CountryRegion',
                'expanded' => false,
            ))
            ->add('foods', EntityType::class, array(
                'class' => 'App:Food',
                'multiple' => true,
                'expanded' => true,
            ))

//            ->add('productCode', NumberType::class, [
//                'label' => 'Артикул',
//                'attr' => [ 'type' => 'number' ]
//            ])
            ->add('name')
            ->add('price')
            ->add('priceStatus', ChoiceType::class, [
                'choices'  => [
                    'доступно' => 1,
                    'недоступно' => 0,
                    'под заказ' => 2,
                ]
            ])
            ->add('color', TextType::class, ['attr' => ['placeholder' => 'красное / белое']])
            ->add('type', TextType::class, ['attr' => ['placeholder' => 'сладкое / сухое']])
//            ->add('grapeSort', TextType::class, ['attr' => ['placeholder' => 'сорта винограда']])
//            ->add('ratings', TextType::class, [
//                'attr' => ['placeholder' => 'рейтинги'],
//                'required' => false,
//            ])
            ->add('alcohol')
            ->add('year')
            ->add('volume')
            ->add('serveTemperature', TextType::class, [
                'label' => 'Темп.подачи(гр.Ц.)',
                'required' => false,
            ])
            ->add('fermentation', TextType::class , [
                'label' => 'Тип ферментации',
                'required' => false,
            ])
            ->add('appellation', TextType::class , [
                'label' => 'Апеллясьон',
                'required' => false,
            ])
            ->add('packing', TextType::class , [
                'label' => 'Упаковка',
                'required' => false,
            ])
            ->add('agingType', TextType::class , [
                'label' => 'Тип выдержки',
                'required' => false,
            ])
            ->add('decantation')
            ->add('slug', TextType::class, [
                'data_class' => null,
                'required' => false,
            ])

        ;

    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Offer::class,
            'allow_extra_fields' => true,
        ));
    }

    /**
     * {@inheritdoc}
     */
//    public function getBlockPrefix()
//    {
//        return 'product';
//    }


}
