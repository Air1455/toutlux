<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\Type\DocumentValidationType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Vich\UploaderBundle\Form\Type\VichImageType;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Utilisateurs')
            ->setPageTitle('index', 'Gestion des utilisateurs')
            ->setPageTitle('detail', fn (User $user) => $user->getDisplayName())
            ->setSearchFields(['email', 'firstName', 'lastName', 'phoneNumber'])
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->showEntityActionsInlined()
            ->setEntityPermission('ROLE_ADMIN');
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addTab('Informations générales');

        yield TextField::new('id')->onlyOnIndex();
        yield EmailField::new('email', 'Email');
        yield TextField::new('firstName', 'Prénom');
        yield TextField::new('lastName', 'Nom');
        yield TextField::new('phoneNumber', 'Téléphone');
        yield TextField::new('phoneNumberIndicatif', 'Indicatif')->hideOnIndex();

        yield ImageField::new('profilePicture', 'Photo de profil')
            ->setBasePath('/uploads/profiles')
            ->onlyOnDetail();

        yield TextField::new('profilePictureFile', 'Photo de profil')
            ->setFormType(VichImageType::class)
            ->onlyOnForms();

        yield ChoiceField::new('userType', 'Type')
            ->setChoices([
                'Locataire' => 'tenant',
                'Propriétaire' => 'landlord',
                'Les deux' => 'both',
                'Agent' => 'agent',
            ]);

        yield ChoiceField::new('status', 'Statut')
            ->setChoices([
                'En attente' => 'pending_verification',
                'Email confirmé' => 'email_confirmed',
                'Documents approuvés' => 'documents_approved',
                'Actif' => 'active',
                'Suspendu' => 'suspended',
            ])
            ->renderAsBadges([
                'pending_verification' => 'warning',
                'email_confirmed' => 'info',
                'documents_approved' => 'primary',
                'active' => 'success',
                'suspended' => 'danger',
            ]);

        yield FormField::addTab('Vérifications');

        yield BooleanField::new('isEmailVerified', 'Email vérifié')
            ->renderAsSwitch(false);
        yield DateTimeField::new('emailVerifiedAt', 'Email vérifié le')
            ->hideOnIndex();

        yield BooleanField::new('isPhoneVerified', 'Téléphone vérifié')
            ->renderAsSwitch(false);
        yield DateTimeField::new('phoneVerifiedAt', 'Téléphone vérifié le')
            ->hideOnIndex();

        yield BooleanField::new('isIdentityVerified', 'Identité vérifiée')
            ->renderAsSwitch(false);
        yield DateTimeField::new('identityVerifiedAt', 'Identité vérifiée le')
            ->hideOnIndex();

        yield BooleanField::new('isFinancialDocsVerified', 'Documents financiers vérifiés')
            ->renderAsSwitch(false);
        yield DateTimeField::new('financialDocsVerifiedAt', 'Documents financiers vérifiés le')
            ->hideOnIndex();

        yield FormField::addTab('Documents')->onlyOnDetail();

        yield ImageField::new('identityCard', 'Pièce d\'identité')
            ->setBasePath('/uploads/documents')
            ->onlyOnDetail();

        yield ImageField::new('selfieWithId', 'Selfie avec ID')
            ->setBasePath('/uploads/documents')
            ->onlyOnDetail();

        yield ImageField::new('incomeProof', 'Justificatif de revenus')
            ->setBasePath('/uploads/documents')
            ->onlyOnDetail();

        yield ImageField::new('ownershipProof', 'Preuve de propriété')
            ->setBasePath('/uploads/documents')
            ->onlyOnDetail();

        yield FormField::addTab('Autres informations');

        yield DateTimeField::new('createdAt', 'Inscrit le')
            ->hideOnForm();
        yield DateTimeField::new('lastActiveAt', 'Dernière activité')
            ->hideOnForm();
        yield TextField::new('googleId', 'Google ID')
            ->onlyOnDetail();
        yield ArrayField::new('metadata', 'Métadonnées')
            ->onlyOnDetail();
    }

    public function configureActions(Actions $actions): Actions
    {
        $validateDocuments = Action::new('validateDocuments', 'Valider documents', 'fa fa-check')
            ->linkToRoute('admin_validate_documents', fn (User $user) => ['id' => $user->getId()])
            ->displayIf(fn (User $user) => $user->hasPendingValidations());

        $sendMessage = Action::new('sendMessage', 'Envoyer message', 'fa fa-envelope')
            ->linkToRoute('admin_send_message', fn (User $user) => ['userId' => $user->getId()]);

        $exportData = Action::new('exportData', 'Exporter données', 'fa fa-download')
            ->linkToRoute('admin_export_user', fn (User $user) => ['id' => $user->getId()])
            ->addCssClass('btn-info');

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_DETAIL, $validateDocuments)
            ->add(Crud::PAGE_DETAIL, $sendMessage)
            ->add(Crud::PAGE_DETAIL, $exportData)
            ->update(Crud::PAGE_INDEX, Action::NEW, fn (Action $action) => $action->setIcon('fa fa-user-plus'))
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn (Action $action) => $action->setIcon('fa fa-edit'))
            ->update(Crud::PAGE_INDEX, Action::DELETE, fn (Action $action) => $action->setIcon('fa fa-trash'));
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('email', 'Email'))
            ->add(TextFilter::new('lastName', 'Nom'))
            ->add(ChoiceFilter::new('status', 'Statut')->setChoices([
                'En attente' => 'pending_verification',
                'Email confirmé' => 'email_confirmed',
                'Documents approuvés' => 'documents_approved',
                'Actif' => 'active',
                'Suspendu' => 'suspended',
            ]))
            ->add(BooleanFilter::new('isEmailVerified', 'Email vérifié'))
            ->add(BooleanFilter::new('isIdentityVerified', 'Identité vérifiée'))
            ->add(BooleanFilter::new('isFinancialDocsVerified', 'Documents financiers vérifiés'))
            ->add(DateTimeFilter::new('createdAt', 'Date d\'inscription'));
    }
}
