<?php

namespace App\Form;

use App\Entity\Appellation;
use App\Entity\CountryRegion;
use App\Entity\Product;
use App\Entity\Vendor;
use Doctrine\DBAL\Types\FloatType;
use Doctrine\ORM\EntityRepository;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class ProductType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
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
            ->add('region',  EntityType::class, array(
                'class' => CountryRegion::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->addOrderBy('c.name', 'ASC');
                },
                'choice_label' => 'name',
                'required' => false,
                'expanded' => false,
            ))
            ->add('foods', EntityType::class, array(
                'class' => 'App:Food',
                'multiple' => true,
                'expanded' => true,
            ))

            ->add('productCode', HiddenType::class, [
                'label' => 'Артикул',
            ])
            ->add('name')
            ->add('price')
            ->add('priceStatus', ChoiceType::class, [
                'choices'  => [
                    'доступно' => 1,
                    'недоступно' => 0,
                    'под заказ' => 2,
                ]
            ])
            // цвет
            ->add('color', HiddenType::class, ['attr' => ['placeholder' => 'красное / белое']])
            ->add('wineColor', EntityType::class, array(
                'class' => 'App:WineColor',
                'label' => 'Цвет',
                'required' => false,
                'expanded' => false,
            ))

//            ->add('type', HiddenType::class, [
//                'label' => 'Содерж.сахара',
//                'attr' => ['placeholder' => 'сладкое / сухое']
//            ])
            ->add('wineSugar', EntityType::class, array(
                'class' => 'App:WineSugar',
                'label' => 'Содерж.сахара',
                'required' => false,
                'expanded' => false,
            ))

            ->add('alcohol', NumberType::class)
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
            ->add('appellation', EntityType::class, array(
                'class' => Appellation::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->addOrderBy('c.name', 'ASC');
                },
                'choice_label' => 'name',
                'required' => false,
                'expanded' => false,
            ))
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
            ->add('metaKeywords')
            ->add('metaDescription')

            ->add('content', TextareaType::class)
//            ->add('content', CKEditorType::class, [
//                    'required' => false,
//                    'config' => array(
//                        'uiColor' => '#ffffff',
//                        'filebrowserBrowseRoute' => 'elfinder',
//                        'filebrowserBrowseRouteParameters' => array(
//                            'instance' => 'default',
//                            'homeFolder' => ''
//                        )
//                    ),
//                ]
//            )
            ->add('announcePicFile', FileType::class, array(
                'label' => 'Announce PIC',
                'data_class' => null,
                'required' => false
            ))
            ->add('contentPicFile', FileType::class, array(
                'label' => 'Content PIC',
                'data_class' => null,
                'required' => false
            ))
            ->add('extraPicFile', FileType::class, array(
                'label' => 'Extra PIC',
                'data_class' => null,
                'required' => false
            ))
            ->add('isActive', CheckboxType::class, ['required' => false])
            ->add('announcePic', HiddenType::class)
            ->add('contentPic', HiddenType::class)
        ;


        $builder->add('productGrapeSorts', CollectionType::class, [
            'label' => false,
            'entry_type' => ProductGrapeSortSubformType::class,
            'entry_options' => ['label' => false],
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
        ]);

        $builder->add('productRatings', CollectionType::class, [
            'label' => false,
            'entry_type' => ProductRatingSubformType::class,
            'entry_options' => ['label' => false],
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
        ]);

    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Product::class,
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
