<?php

namespace App\Controller\Admin;

use App\Entity\House;
use App\Entity\HouseImage;
use App\Form\Type\HouseImageType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class HouseCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return House::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Annonce')
            ->setEntityLabelInPlural('Annonces')
            ->setPageTitle('index', 'Gestion des annonces')
            ->setSearchFields(['address', 'city', 'shortDescription'])
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('user', 'Propriétaire')
            ->autocomplete()
            ->formatValue(fn ($value, $entity) => $entity->getUser()->getDisplayName());

        yield TextField::new('shortDescription', 'Description courte');
        yield TextareaField::new('longDescription', 'Description complète')
            ->hideOnIndex();

        yield MoneyField::new('price', 'Prix')
            ->setCurrency('XOF')
            ->setStoredAsCents(false);

        yield ChoiceField::new('currency', 'Devise')
            ->setChoices([
                'FCFA' => 'XOF',
                'Euro' => 'EUR',
                'Dollar' => 'USD',
            ]);

        yield BooleanField::new('isForRent', 'Location')
            ->renderAsSwitch();

        yield TextField::new('address', 'Adresse');
        yield TextField::new('city', 'Ville');
        yield TextField::new('country', 'Pays');

        yield IntegerField::new('bedrooms', 'Chambres');
        yield IntegerField::new('bathrooms', 'Salles de bain');
        yield TextField::new('surface', 'Surface');

        yield ChoiceField::new('type', 'Type')
            ->setChoices([
                'Appartement' => 'apartment',
                'Maison' => 'house',
                'Villa' => 'villa',
                'Studio' => 'studio',
            ]);

        yield CollectionField::new('images', 'Images')
            ->setEntryType(HouseImageType::class)
            ->setFormTypeOptions([
                'by_reference' => false,
            ])
            ->onlyOnForms();

        yield ChoiceField::new('status', 'Statut')
            ->setChoices([
                'Active' => 'active',
                'Suspendue' => 'suspended',
                'En attente' => 'pending',
                'Rejetée' => 'rejected',
            ])
            ->renderAsBadges([
                'active' => 'success',
                'suspended' => 'warning',
                'pending' => 'info',
                'rejected' => 'danger',
            ]);
    }
}
