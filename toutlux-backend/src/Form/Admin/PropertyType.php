<?php

namespace App\Form\Admin;

use App\Entity\Property;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PropertyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'required' => true
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => true,
                'attr' => ['rows' => 6]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'Vente' => 'sale',
                    'Location' => 'rent'
                ],
                'required' => true
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Prix',
                'currency' => 'EUR',
                'required' => true
            ])
            ->add('surface', IntegerType::class, [
                'label' => 'Surface (m²)',
                'required' => true
            ])
            ->add('rooms', IntegerType::class, [
                'label' => 'Nombre de pièces',
                'required' => true
            ])
            ->add('bedrooms', IntegerType::class, [
                'label' => 'Chambres',
                'required' => true
            ])
            ->add('bathrooms', IntegerType::class, [
                'label' => 'Salles de bain',
                'required' => true
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'required' => true
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'required' => true
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'required' => true
            ])
            ->add('latitude', TextType::class, [
                'label' => 'Latitude',
                'required' => false
            ])
            ->add('longitude', TextType::class, [
                'label' => 'Longitude',
                'required' => false
            ])
            ->add('features', ChoiceType::class, [
                'label' => 'Caractéristiques',
                'choices' => [
                    'Garage' => 'garage',
                    'Parking' => 'parking',
                    'Balcon' => 'balcony',
                    'Terrasse' => 'terrace',
                    'Jardin' => 'garden',
                    'Piscine' => 'pool',
                    'Ascenseur' => 'elevator',
                    'Cave' => 'cellar',
                    'Climatisation' => 'air_conditioning',
                    'Chauffage' => 'heating',
                    'Cheminée' => 'fireplace',
                    'Alarme' => 'alarm'
                ],
                'multiple' => true,
                'expanded' => true,
                'required' => false
            ])
            ->add('available', CheckboxType::class, [
                'label' => 'Disponible',
                'required' => false
            ])
            ->add('verified', CheckboxType::class, [
                'label' => 'Vérifié',
                'required' => false
            ])
            ->add('featured', CheckboxType::class, [
                'label' => 'Mise en avant',
                'required' => false
            ])
            ->add('owner', EntityType::class, [
                'label' => 'Propriétaire',
                'class' => User::class,
                'choice_label' => function (User $user) {
                    return sprintf('%s %s (%s)',
                        $user->getFirstName(),
                        $user->getLastName(),
                        $user->getEmail()
                    );
                },
                'required' => true
            ])
            ->add('metaTitle', TextType::class, [
                'label' => 'Meta titre (SEO)',
                'required' => false
            ])
            ->add('metaDescription', TextareaType::class, [
                'label' => 'Meta description (SEO)',
                'required' => false,
                'attr' => ['rows' => 3]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Property::class,
        ]);
    }
}
