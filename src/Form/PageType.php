<?php

namespace App\Form;

use App\Entity\Page;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('slug')
            ->add('picFile', FileType::class, array(
                'label' => 'PIC',
                'data_class' => null,
                'required' => false
            ))
            ->add('content', CKEditorType::class, [
                'config' => array(
                    'uiColor' => '#ffffff',
//                        'filebrowserBrowseRoute' => 'elfinder',
//                        'filebrowserBrowseRouteParameters' => array(
//                            'instance' => 'default',
//                            'homeFolder' => ''
//                        )
                ),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Page::class,
        ]);
    }
}
