<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use App\Service\ProfileCompletionService;
use App\Service\EmailVerificationService;
use App\Service\ListingPermissionService;

class UserCrudController extends AbstractCrudController
{
    private $entityManager;
    private $profileCompletionService;
    private $emailVerificationService;
    private $listingPermissionService;

    public function __construct(EntityManagerInterface $entityManager, ProfileCompletionService $profileCompletionService, EmailVerificationService $emailVerificationService, ListingPermissionService $listingPermissionService)
    {
        $this->entityManager = $entityManager;
        $this->profileCompletionService = $profileCompletionService;
        $this->emailVerificationService = $emailVerificationService;
        $this->listingPermissionService = $listingPermissionService;
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('User')
            ->setEntityLabelInPlural('Users')
            ->setSearchFields(['email', 'firstName', 'lastName']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $validateIdentityDocuments = Action::new('validateIdentityDocuments', 'Valider Pièces d\'Identité')
            ->linkToCrudAction('validateIdentityDocuments')
            ->addCssClass('btn btn-success')
            ->displayIf(function (User $user) { return $user->hasIdentityDocuments() && !$user->isIdentityVerified(); });

        $rejectIdentityDocuments = Action::new('rejectIdentityDocuments', 'Refuser Pièces d\'Identité')
            ->linkToCrudAction('rejectIdentityDocuments')
            ->addCssClass('btn btn-danger')
            ->displayIf(function (User $user) { return $user->hasIdentityDocuments() && !$user->isIdentityVerified(); });

        $validateFinancialDocuments = Action::new('validateFinancialDocuments', 'Valider Justificatifs Financiers')
            ->linkToCrudAction('validateFinancialDocuments')
            ->addCssClass('btn btn-success')
            ->displayIf(function (User $user) { return $user->hasFinancialDocuments() && !$user->isFinancialDocsVerified(); });

        $rejectFinancialDocuments = Action::new('rejectFinancialDocuments', 'Refuser Justificatifs Financiers')
            ->linkToCrudAction('rejectFinancialDocuments')
            ->addCssClass('btn btn-danger')
            ->displayIf(function (User $user) { return $user->hasFinancialDocuments() && !$user->isFinancialDocsVerified(); });

        return $actions
            ->add(Crud::PAGE_DETAIL, $validateIdentityDocuments)
            ->add(Crud::PAGE_DETAIL, $rejectIdentityDocuments)
            ->add(Crud::PAGE_DETAIL, $validateFinancialDocuments)
            ->add(Crud::PAGE_DETAIL, $rejectFinancialDocuments)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('email'),
            ArrayField::new('roles'),
            TextField::new('firstName'),
            TextField::new('lastName'),
            TextField::new('phoneNumber'),
            TextField::new('phoneNumberIndicatif'),

            // Profile Picture
            ImageField::new('profilePicture')
                ->setBasePath('/uploads/users')
                ->setUploadDir('public/uploads/users')
                ->setUploadedFileNamePattern('[randomhash].[extension]')
                ->setFormTypeOptions([
                    'attr' => [
                        'accept' => 'image/*',
                    ],
                ])
                ->setLabel('Profile Picture'),
            TextField::new('profilePictureFile')->setFormType('Vich\UploaderBundle\Form\Type\VichImageType')->onlyOnForms(),

            ChoiceField::new('userType')->setChoices([
                'Tenant' => 'tenant',
                'Landlord' => 'landlord',
                'Both' => 'both',
                'Agent' => 'agent',
            ]),
            TextField::new('occupation'),
            ChoiceField::new('incomeSource')->setChoices([
                'Salary' => 'salary',
                'Business' => 'business',
                'Investment' => 'investment',
                'Pension' => 'pension',
                'Rental' => 'rental',
                'Other' => 'other',
            ]),

            ChoiceField::new('identityCardType')->setChoices([
                'National ID' => 'national_id',
                'Passport' => 'passport',
                'Driving License' => 'driving_license',
            ]),
            TextField::new('identityCard'),
            TextField::new('identityCardFile')->setFormType('Vich\UploaderBundle\Form\Type\VichFileType')->onlyOnForms(),

            TextField::new('selfieWithId'),
            TextField::new('selfieWithIdFile')->setFormType('Vich\UploaderBundle\Form\Type\VichFileType')->onlyOnForms(),

            TextField::new('incomeProof'),
            TextField::new('incomeProofFile')->setFormType('Vich\UploaderBundle\Form\Type\VichFileType')->onlyOnForms(),

            TextField::new('ownershipProof'),
            TextField::new('ownershipProofFile')->setFormType('Vich\UploaderBundle\Form\Type\VichFileType')->onlyOnForms(),

            BooleanField::new('isEmailVerified'),
            BooleanField::new('isPhoneVerified'),
            BooleanField::new('isIdentityVerified'),
            BooleanField::new('isFinancialDocsVerified'),
            DateTimeField::new('emailVerifiedAt'),
            DateTimeField::new('phoneVerifiedAt'),
            DateTimeField::new('identityVerifiedAt'),
            DateTimeField::new('financialDocsVerifiedAt'),
            TextField::new('emailConfirmationToken'),
            DateTimeField::new('emailConfirmationTokenExpiresAt'),
            IntegerField::new('emailVerificationAttempts'),
            DateTimeField::new('lastEmailVerificationRequestAt'),
            BooleanField::new('termsAccepted'),
            BooleanField::new('privacyAccepted'),
            BooleanField::new('marketingAccepted'),
            DateTimeField::new('termsAcceptedAt'),
            DateTimeField::new('privacyAcceptedAt'),
            DateTimeField::new('marketingAcceptedAt'),
            DateTimeField::new('createdAt'),
            DateTimeField::new('updatedAt'),
            ChoiceField::new('status')->setChoices([
                'Pending Verification' => 'pending_verification',
                'Active' => 'active',
                'Suspended' => 'suspended',
            ]),
            DateTimeField::new('lastActiveAt'),
            TextField::new('language'),
            TextField::new('googleId'),
            IntegerField::new('profileViews'),
            ArrayField::new('metadata'),
        ];
    }

    public function validateIdentityDocuments(AdminContext $context): Response
    {
        /** @var User $user */
        $user = $context->getEntity()->getInstance();

        $user->setIsIdentityVerified(true);
        $this->entityManager->flush();

        $this->profileCompletionService->calculateTrustScore($user);
        $this->profileCompletionService->notifyUserOfValidationStatus($user, 'pièces d\'identité', true);

        $this->addFlash('success', sprintf('Les pièces d\'identité de %s ont été validées.', $user->getEmail()));

        return $this->redirect($context->getReferrer());
    }

    public function rejectIdentityDocuments(AdminContext $context): Response
    {
        /** @var User $user */
        $user = $context->getEntity()->getInstance();

        $user->setIsIdentityVerified(false);
        // You might want to add a field for rejection reason
        $this->entityManager->flush();

        $this->profileCompletionService->calculateTrustScore($user);
        $this->profileCompletionService->notifyUserOfValidationStatus($user, 'pièces d\'identité', false);

        $this->addFlash('warning', sprintf('Les pièces d\'identité de %s ont été refusées.', $user->getEmail()));

        return $this->redirect($context->getReferrer());
    }

    public function validateFinancialDocuments(AdminContext $context): Response
    {
        /** @var User $user */
        $user = $context->getEntity()->getInstance();

        $user->setIsFinancialDocsVerified(true);
        $this->entityManager->flush();

        $this->profileCompletionService->calculateTrustScore($user);
        $this->profileCompletionService->notifyUserOfValidationStatus($user, 'justificatifs financiers', true);

        $this->addFlash('success', sprintf('Les justificatifs financiers de %s ont été validés.', $user->getEmail()));

        return $this->redirect($context->getReferrer());
    }

    public function rejectFinancialDocuments(AdminContext $context): Response
    {
        /** @var User $user */
        $user = $context->getEntity()->getInstance();

        $user->setIsFinancialDocsVerified(false);
        // You might want to add a field for rejection reason
        $this->entityManager->flush();

        $this->profileCompletionService->calculateTrustScore($user);
        $this->profileCompletionService->notifyUserOfValidationStatus($user, 'justificatifs financiers', false);

        $this->addFlash('warning', sprintf('Les justificatifs financiers de %s ont été refusés.', $user->getEmail()));

        return $this->redirect($context->getReferrer());
    }
}