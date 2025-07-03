<?php

namespace App\Form\Type;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ProfileUpdateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isRegistration = $options['is_registration'] ?? false;
        $currentStep = $options['current_step'] ?? null;

        // Section 1: Informations personnelles
        if (!$currentStep || $currentStep === 'personal') {
            $builder
                ->add('firstName', TextType::class, [
                    'label' => 'Prénom',
                    'required' => $isRegistration,
                    'attr' => [
                        'placeholder' => 'Jean',
                        'autocomplete' => 'given-name',
                        'class' => 'form-control'
                    ],
                    'constraints' => [
                        new Assert\Length([
                            'max' => 100,
                            'maxMessage' => 'Le prénom ne peut pas dépasser {{ limit }} caractères'
                        ])
                    ]
                ])
                ->add('lastName', TextType::class, [
                    'label' => 'Nom',
                    'required' => $isRegistration,
                    'attr' => [
                        'placeholder' => 'Dupont',
                        'autocomplete' => 'family-name',
                        'class' => 'form-control'
                    ],
                    'constraints' => [
                        new Assert\Length([
                            'max' => 100,
                            'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères'
                        ])
                    ]
                ])
                ->add('phoneNumber', TelType::class, [
                    'label' => 'Numéro de téléphone',
                    'required' => false,
                    'attr' => [
                        'placeholder' => '+33 6 12 34 56 78',
                        'autocomplete' => 'tel',
                        'class' => 'form-control phone-input',
                        'data-intl-tel-input' => true
                    ],
                    'constraints' => [
                        new Assert\Length([
                            'max' => 20,
                            'maxMessage' => 'Le numéro de téléphone ne peut pas dépasser {{ limit }} caractères'
                        ]),
                        new Assert\Regex([
                            'pattern' => '/^[0-9\+\-\.\(\)\s]+$/',
                            'message' => 'Le numéro de téléphone contient des caractères invalides'
                        ])
                    ]
                ])
                ->add('birthDate', BirthdayType::class, [
                    'label' => 'Date de naissance',
                    'widget' => 'single_text',
                    'required' => false,
                    'attr' => [
                        'class' => 'form-control',
                        'max' => (new \DateTime())->format('Y-m-d')
                    ],
                    'constraints' => [
                        new Assert\LessThan([
                            'value' => 'today',
                            'message' => 'La date de naissance doit être dans le passé'
                        ])
                    ]
                ]);
        }

        // Section 2: Adresse
        if (!$currentStep || $currentStep === 'address') {
            $builder
                ->add('address', TextType::class, [
                    'label' => 'Adresse',
                    'required' => false,
                    'attr' => [
                        'placeholder' => '123 rue de la Paix',
                        'autocomplete' => 'street-address',
                        'class' => 'form-control'
                    ],
                    'constraints' => [
                        new Assert\Length([
                            'max' => 255,
                            'maxMessage' => 'L\'adresse ne peut pas dépasser {{ limit }} caractères'
                        ])
                    ]
                ])
                ->add('city', TextType::class, [
                    'label' => 'Ville',
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'Paris',
                        'autocomplete' => 'address-level2',
                        'class' => 'form-control'
                    ],
                    'constraints' => [
                        new Assert\Length([
                            'max' => 100,
                            'maxMessage' => 'Le nom de la ville ne peut pas dépasser {{ limit }} caractères'
                        ])
                    ]
                ])
                ->add('postalCode', TextType::class, [
                    'label' => 'Code postal',
                    'required' => false,
                    'attr' => [
                        'placeholder' => '75001',
                        'autocomplete' => 'postal-code',
                        'class' => 'form-control',
                        'pattern' => '[0-9]{5}',
                        'maxlength' => 5
                    ],
                    'constraints' => [
                        new Assert\Regex([
                            'pattern' => '/^[0-9]{5}$/',
                            'message' => 'Le code postal doit contenir exactement 5 chiffres'
                        ])
                    ]
                ])
                ->add('country', CountryType::class, [
                    'label' => 'Pays',
                    'required' => false,
                    'preferred_choices' => ['FR', 'BE', 'CH', 'CA', 'LU', 'MC'],
                    'placeholder' => 'Sélectionnez un pays',
                    'attr' => [
                        'class' => 'form-control form-select',
                        'autocomplete' => 'country'
                    ],
                    'constraints' => [
                        new Assert\Country([
                            'message' => 'Veuillez sélectionner un pays valide'
                        ])
                    ]
                ]);
        }

        // Section 3: À propos et avatar
        if (!$currentStep || $currentStep === 'about') {
            $builder
                ->add('bio', TextareaType::class, [
                    'label' => 'À propos de vous',
                    'required' => false,
                    'attr' => [
                        'rows' => 4,
                        'placeholder' => 'Parlez-nous un peu de vous...',
                        'class' => 'form-control',
                        'maxlength' => 1000
                    ],
                    'constraints' => [
                        new Assert\Length([
                            'max' => 1000,
                            'maxMessage' => 'La biographie ne peut pas dépasser {{ limit }} caractères'
                        ])
                    ]
                ])
                ->add('avatarFile', FileType::class, [
                    'label' => 'Photo de profil',
                    'mapped' => false,
                    'required' => false,
                    'attr' => [
                        'class' => 'form-control',
                        'accept' => 'image/jpeg,image/png,image/webp'
                    ],
                    'constraints' => [
                        new Assert\File([
                            'maxSize' => '5M',
                            'mimeTypes' => [
                                'image/jpeg',
                                'image/png',
                                'image/webp'
                            ],
                            'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG ou WebP)',
                            'maxSizeMessage' => 'L\'image ne doit pas dépasser {{ limit }}{{ suffix }}'
                        ])
                    ],
                    'help' => 'Format acceptés : JPEG, PNG, WebP. Taille max : 5 MB'
                ]);
        }

        // Section 4: Préférences de notification
        if (!$currentStep || $currentStep === 'notifications') {
            $builder
                ->add('emailNotificationsEnabled', CheckboxType::class, [
                    'label' => 'Recevoir des notifications par email',
                    'required' => false,
                    'attr' => [
                        'class' => 'form-check-input'
                    ],
                    'help' => 'Recevez des alertes sur les nouvelles propriétés et messages'
                ])
                ->add('smsNotificationsEnabled', CheckboxType::class, [
                    'label' => 'Recevoir des notifications par SMS',
                    'required' => false,
                    'attr' => [
                        'class' => 'form-check-input'
                    ],
                    'help' => 'Recevez des alertes importantes par SMS (nécessite un numéro de téléphone)'
                ])
                ->add('notificationFrequency', ChoiceType::class, [
                    'label' => 'Fréquence des notifications',
                    'mapped' => false,
                    'required' => false,
                    'choices' => [
                        'Immédiat' => 'immediate',
                        'Quotidien' => 'daily',
                        'Hebdomadaire' => 'weekly',
                        'Mensuel' => 'monthly'
                    ],
                    'placeholder' => false,
                    'data' => 'immediate',
                    'attr' => [
                        'class' => 'form-control form-select'
                    ]
                ])
                ->add('notificationTypes', ChoiceType::class, [
                    'label' => 'Types de notifications',
                    'mapped' => false,
                    'required' => false,
                    'choices' => [
                        'Nouveaux messages' => 'new_messages',
                        'Nouvelles propriétés' => 'new_properties',
                        'Mises à jour de documents' => 'document_updates',
                        'Alertes de sécurité' => 'security_alerts',
                        'Promotions et offres' => 'promotions'
                    ],
                    'multiple' => true,
                    'expanded' => true,
                    'attr' => [
                        'class' => 'form-check'
                    ]
                ]);
        }

        // Ajout conditionnel du champ email pour la mise à jour
        if ($options['allow_email_change']) {
            $builder->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'required' => true,
                'attr' => [
                    'placeholder' => 'email@example.com',
                    'autocomplete' => 'email',
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'L\'adresse email est requise'
                    ]),
                    new Assert\Email([
                        'message' => 'L\'adresse email n\'est pas valide'
                    ])
                ],
                'help' => 'Attention : changer votre email nécessitera une nouvelle vérification'
            ]);
        }

        // Event listener pour valider la cohérence des données
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();

            // Si SMS activé, vérifier qu'un numéro de téléphone est fourni
            if (isset($data['smsNotificationsEnabled']) && $data['smsNotificationsEnabled'] && empty($data['phoneNumber'])) {
                $data['smsNotificationsEnabled'] = false;
            }

            $event->setData($data);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_registration' => false,
            'current_step' => null,
            'allow_email_change' => false,
            'validation_groups' => function($form) {
                $groups = ['Default'];

                if ($form->getConfig()->getOption('is_registration')) {
                    $groups[] = 'registration';
                }

                $currentStep = $form->getConfig()->getOption('current_step');
                if ($currentStep) {
                    $groups[] = 'step_' . $currentStep;
                }

                return $groups;
            }
        ]);

        $resolver->setAllowedTypes('is_registration', 'bool');
        $resolver->setAllowedTypes('current_step', ['null', 'string']);
        $resolver->setAllowedTypes('allow_email_change', 'bool');

        $resolver->setAllowedValues('current_step', [null, 'personal', 'address', 'about', 'notifications']);
    }
}
