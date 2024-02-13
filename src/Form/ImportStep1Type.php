<?php

namespace App\Form;

use App\Entity\ImportLog;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImportStep1Type extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, ['attr' => [
                'placeholder' => 'Например "Италия - новинки"'
            ]])
            ->add('category')
            ->add('supplier')
            ->add('csvFile', FileType::class, array(
                'label' => 'CSV file',
                'data_class' => null,
                'required' => false,
//                'mapped' => false,
//                'constraints' => [
//                    new File([
//                        'maxSize' => '1024k',
//                        'mimeTypes' => [
//                            'text/csv',
//                        ],
//                        'mimeTypesMessage' => 'Please upload a valid CSV document',
//                    ])
//                ],
            ))
            ->add('csvDelimiter', ChoiceType::class, [
                'label' => 'Разделитель столбцов',
                'choices'  => [
                    ';' => ';',
                    ',' => ',',
                    'tab' => chr(9),
                ]
            ])
            ->add('fileContainHeader', CheckboxType::class, [
                'label' => 'файл содержит заголовок?',
                'required' => false,
            ])
//            ->add('isComplete', CheckboxType::class, [
//                'label' => 'импорт завершён?',
//                'required' => false,
//            ])
        ;

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ImportLog::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'import_step1';
    }

}