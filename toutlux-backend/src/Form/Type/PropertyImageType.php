<?php

namespace App\Form\Type;

use App\Entity\PropertyImage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class PropertyImageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('imageFile', FileType::class, [
                'label' => 'Image',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp'
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG ou WebP)',
                    ])
                ],
            ])
            ->add('alt', TextType::class, [
                'label' => 'Texte alternatif',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Description de l\'image pour l\'accessibilité'
                ]
            ])
            ->add('isMain', CheckboxType::class, [
                'label' => 'Image principale',
                'required' => false
            ])
            ->add('position', IntegerType::class, [
                'label' => 'Position',
                'required' => false,
                'attr' => [
                    'min' => 0
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PropertyImage::class,
        ]);
    }
}
