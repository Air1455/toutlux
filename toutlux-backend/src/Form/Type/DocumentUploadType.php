<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class DocumentUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Type de document',
                'choices' => [
                    'Pièce d\'identité' => 'identity',
                    'Document financier' => 'financial'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner un type de document'])
                ]
            ])
            ->add('subType', ChoiceType::class, [
                'label' => 'Sous-type',
                'choices' => [
                    'Identité' => [
                        'Carte d\'identité' => 'id_card',
                        'Passeport' => 'passport',
                        'Permis de conduire' => 'driver_license',
                        'Selfie avec document' => 'selfie'
                    ],
                    'Financier' => [
                        'Relevé bancaire' => 'bank_statement',
                        'Bulletin de salaire' => 'payslip',
                        'Avis d\'imposition' => 'tax_return',
                        'Autre justificatif' => 'other'
                    ]
                ],
                'required' => false
            ])
            ->add('document', FileType::class, [
                'label' => 'Fichier',
                'mapped' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'application/pdf',
                            'image/jpeg',
                            'image/png',
                            'image/webp'
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger un fichier valide (PDF, JPEG, PNG ou WebP)',
                    ])
                ],
            ])
            ->add('title', TextType::class, [
                'label' => 'Titre du document',
                'required' => false
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 3]
            ])
            ->add('documentNumber', TextType::class, [
                'label' => 'Numéro du document',
                'required' => false
            ])
            ->add('issuingAuthority', TextType::class, [
                'label' => 'Autorité émettrice',
                'required' => false
            ])
            ->add('issueDate', DateType::class, [
                'label' => 'Date d\'émission',
                'widget' => 'single_text',
                'required' => false
            ])
            ->add('expiresAt', DateType::class, [
                'label' => 'Date d\'expiration',
                'widget' => 'single_text',
                'required' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
