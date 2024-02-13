<?php

namespace App\Form\Front;

use App\Entity\Country;
use App\Entity\Vendor;
use App\Entity\WineCard;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CabinetWineCardType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Название ресторана'
            ])
//            ->add('country', EntityType::class, array(
//                'class' => Country::class,
//                'query_builder' => function (EntityRepository $er) {
//                    return $er->createQueryBuilder('c')
//                        ->addOrderBy('c.name', 'ASC');
//                },
//                'choice_label' => 'name',
//                'required' => false,
//                'expanded' => false,
//            ))
//            ->add('city', TextType::class, [
//                'label' => 'Город'
//            ])
//            ->add('address', TextType::class, [
//                'label' => 'Адрес'
//            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => WineCard::class,
        ]);
    }
}
