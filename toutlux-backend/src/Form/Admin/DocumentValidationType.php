<?php

namespace App\Form\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DocumentValidationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('action', ChoiceType::class, [
                'label' => 'Action',
                'choices' => [
                    'Approuver' => 'approve',
                    'Rejeter' => 'reject'
                ],
                'expanded' => true,
                'required' => true
            ])
            ->add('reason', TextType::class, [
                'label' => 'Raison du rejet',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Requis si rejet'
                ]
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes de validation',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Notes internes (non visibles par l\'utilisateur)'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
