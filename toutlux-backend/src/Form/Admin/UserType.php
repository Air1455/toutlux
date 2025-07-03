<?php

namespace App\Form\Admin;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
                'help' => 'Laissez vide pour conserver le mot de passe actuel'
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'required' => false
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'required' => false
            ])
            ->add('phoneNumber', TextType::class, [
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
                'attr' => ['maxlength' => 2]
            ])
            ->add('bio', TextareaType::class, [
                'label' => 'Biographie',
                'required' => false,
                'attr' => ['rows' => 4]
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Rôles',
                'choices' => [
                    'Utilisateur' => 'ROLE_USER',
                    'Administrateur' => 'ROLE_ADMIN',
                    'Super Administrateur' => 'ROLE_SUPER_ADMIN'
                ],
                'multiple' => true,
                'expanded' => true
            ])
            ->add('emailVerified', CheckboxType::class, [
                'label' => 'Email vérifié',
                'required' => false
            ])
            ->add('phoneVerified', CheckboxType::class, [
                'label' => 'Téléphone vérifié',
                'required' => false
            ])
            ->add('profileCompleted', CheckboxType::class, [
                'label' => 'Profil complété',
                'required' => false
            ])
            ->add('identityVerified', CheckboxType::class, [
                'label' => 'Identité vérifiée',
                'required' => false
            ])
            ->add('financialVerified', CheckboxType::class, [
                'label' => 'Documents financiers vérifiés',
                'required' => false
            ])
            ->add('termsAccepted', CheckboxType::class, [
                'label' => 'Conditions acceptées',
                'required' => false
            ])
            ->add('emailNotificationsEnabled', CheckboxType::class, [
                'label' => 'Notifications email activées',
                'required' => false
            ])
            ->add('smsNotificationsEnabled', CheckboxType::class, [
                'label' => 'Notifications SMS activées',
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
