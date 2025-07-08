<?php

namespace App\Form\Admin;

use App\Entity\User;
use App\Enum\UserRole;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe',
                'mapped' => false,
                'required' => false,
                'help' => 'Laissez vide pour conserver le mot de passe actuel',
                'attr' => ['autocomplete' => 'new-password']
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'required' => false
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'required' => false
            ])
            ->add('phone', TextType::class, [
                'label' => 'Téléphone',
                'required' => false
            ])
            ->add('birthDate', DateType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
                'required' => false
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'required' => false
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'required' => false
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'required' => false
            ])
            ->add('country', TextType::class, [
                'label' => 'Pays',
                'required' => false,
                'attr' => ['maxlength' => 2, 'placeholder' => 'TG']
            ])
            ->add('bio', TextareaType::class, [
                'label' => 'Biographie',
                'required' => false,
                'attr' => ['rows' => 4]
            ])
            ->add('avatarFile', FileType::class, [
                'label' => 'Photo de profil',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG, WebP)',
                    ])
                ],
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Rôles',
                'choices' => [
                    'Utilisateur' => UserRole::USER->value,
                    'Administrateur' => UserRole::ADMIN->value,
                    'Super Administrateur' => UserRole::SUPER_ADMIN->value
                ],
                'multiple' => true,
                'expanded' => true
            ])
            // Fixed: Use correct property names from User entity
            ->add('isEmailVerified', CheckboxType::class, [
                'label' => 'Email vérifié',
                'required' => false,
                'attr' => ['class' => 'custom-control-input']
            ])
            ->add('phoneVerified', CheckboxType::class, [
                'label' => 'Téléphone vérifié',
                'required' => false,
                'attr' => ['class' => 'custom-control-input']
            ])
            ->add('profileCompleted', CheckboxType::class, [
                'label' => 'Profil complété',
                'required' => false,
                'disabled' => true, // Should be auto-calculated
                'help' => 'Calculé automatiquement',
                'attr' => ['class' => 'custom-control-input']
            ])
            ->add('identityVerified', CheckboxType::class, [
                'label' => 'Identité vérifiée',
                'required' => false,
                'attr' => ['class' => 'custom-control-input']
            ])
            ->add('financialVerified', CheckboxType::class, [
                'label' => 'Documents financiers vérifiés',
                'required' => false,
                'attr' => ['class' => 'custom-control-input']
            ])
            ->add('termsAccepted', CheckboxType::class, [
                'label' => 'Conditions acceptées',
                'required' => false,
                'attr' => ['class' => 'custom-control-input']
            ])
            ->add('emailNotificationsEnabled', CheckboxType::class, [
                'label' => 'Notifications email activées',
                'required' => false,
                'attr' => ['class' => 'custom-control-input']
            ])
            ->add('smsNotificationsEnabled', CheckboxType::class, [
                'label' => 'Notifications SMS activées',
                'required' => false,
                'attr' => ['class' => 'custom-control-input']
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'Compte actif',
                'required' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
