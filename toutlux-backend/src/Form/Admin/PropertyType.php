<?php

namespace App\Form\Admin;

use App\Entity\Property;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
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
                'required' => true,
                'attr' => ['placeholder' => 'Ex: Villa moderne avec piscine']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => true,
                'attr' => [
                    'rows' => 6,
                    'placeholder' => 'Description détaillée de la propriété...'
                ]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'Vente' => 'sale',
                    'Location' => 'rent'
                ],
                'required' => true
            ])
            ->add('price', NumberType::class, [
                'label' => 'Prix (€)',
                'required' => true,
                'scale' => 2,
                'attr' => ['placeholder' => '0']
            ])
            ->add('surface', IntegerType::class, [
                'label' => 'Surface (m²)',
                'required' => true,
                'attr' => ['placeholder' => '0', 'min' => 1]
            ])
            ->add('rooms', IntegerType::class, [
                'label' => 'Nombre de pièces',
                'required' => true,
                'attr' => ['min' => 1]
            ])
            ->add('bedrooms', IntegerType::class, [
                'label' => 'Chambres',
                'required' => true,
                'attr' => ['min' => 0]
            ])
            ->add('bathrooms', IntegerType::class, [
                'label' => 'Salles de bain',
                'required' => true,
                'attr' => ['min' => 0]
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'required' => true,
                'attr' => ['placeholder' => 'Adresse complète de la propriété']
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'required' => true,
                'attr' => ['placeholder' => 'Lomé']
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'required' => true,
                'attr' => ['maxlength' => 10]
            ])
            ->add('latitude', TextType::class, [
                'label' => 'Latitude',
                'required' => false,
                'attr' => ['placeholder' => '6.1319']
            ])
            ->add('longitude', TextType::class, [
                'label' => 'Longitude',
                'required' => false,
                'attr' => ['placeholder' => '1.2228']
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
                    'Alarme' => 'alarm',
                    'Interphone' => 'intercom',
                    'Digicode' => 'digicode',
                    'Gardien' => 'concierge',
                    'Fibre optique' => 'fiber',
                    'Meublé' => 'furnished',
                    'Cuisine équipée' => 'fitted_kitchen'
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
                        $user->getFirstName() ?: '',
                        $user->getLastName() ?: '',
                        $user->getEmail()
                    );
                },
                'required' => true,
                'attr' => ['class' => 'form-control select2']
            ])
            ->add('metaTitle', TextType::class, [
                'label' => 'Meta titre (SEO)',
                'required' => false,
                'attr' => ['maxlength' => 255]
            ])
            ->add('metaDescription', TextareaType::class, [
                'label' => 'Meta description (SEO)',
                'required' => false,
                'attr' => ['rows' => 3, 'maxlength' => 300]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Property::class,
        ]);
    }
}
