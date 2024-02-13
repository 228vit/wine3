<?php

namespace App\Form;

use App\Entity\Country;
use App\Service\CountryService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CountryType extends AbstractType
{
    private $countryService;

    public function __construct()
    {
        $this->countryService = new CountryService();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('codeAlpha2',  TextType::class,
                [
                    'label' => 'Код страны (2 буквы)',
                    'attr' => ['maxlength' => 2]
                ])
            ->add('worldPart', ChoiceType::class, [
                'label' => 'Часть света',
                'choices' => array_merge([
                        '' => '',
                    ], Country::WORLD_PARTS_INVERSED
                ),
            ])
        ;
        $builder->add('aliases', CollectionType::class, [
            'label' => false,
            'entry_type' => CountryAliasSubFormType::class,
            'entry_options' => ['label' => false],
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
        ]);

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Country::class,
        ]);
    }
}
