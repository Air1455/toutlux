<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\HttpFoundation\Response;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('User')
            ->setEntityLabelInPlural('Users')
            ->setPageTitle('index', 'User Management')
            ->setPageTitle('detail', fn (User $user) => sprintf('User: %s', $user->getEmail()))
            ->setPageTitle('edit', fn (User $user) => sprintf('Edit User: %s', $user->getEmail()))
            ->setSearchFields(['email', 'profile.firstName', 'profile.lastName', 'profile.phoneNumber'])
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->showEntityActionsInlined()
            ->setFormOptions([
                'validation_groups' => ['Default']
            ]);
    }

    public function configureActions(Actions $actions): Actions
    {
        $viewProfile = Action::new('viewProfile', 'View Profile', 'fa fa-user')
            ->linkToCrudAction('viewProfile')
            ->displayIf(fn (User $user) => $user->getProfile() !== null);

        $viewDocuments = Action::new('viewDocuments', 'Documents', 'fa fa-file')
            ->linkToCrudAction('viewDocuments')
            ->displayIf(fn (User $user) => count($user->getDocuments()) > 0);

        $sendEmail = Action::new('sendEmail', 'Send Email', 'fa fa-envelope')
            ->linkToCrudAction('sendEmail');

        return $actions
            ->add(Crud::PAGE_INDEX, $viewProfile)
            ->add(Crud::PAGE_INDEX, $viewDocuments)
            ->add(Crud::PAGE_DETAIL, $viewProfile)
            ->add(Crud::PAGE_DETAIL, $viewDocuments)
            ->add(Crud::PAGE_DETAIL, $sendEmail)
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setIcon('fa fa-user-plus');
            })
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setIcon('fa fa-edit');
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setIcon('fa fa-trash')
                    ->displayIf(fn (User $user) => !in_array('ROLE_ADMIN', $user->getRoles()));
            });
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            IdField::new('id')->hideOnForm(),
            EmailField::new('email'),
            BooleanField::new('isVerified', 'Email Verified')
                ->renderAsSwitch(false),
            ChoiceField::new('roles')
                ->setChoices([
                    'User' => 'ROLE_USER',
                    'Moderator' => 'ROLE_MODERATOR',
                    'Admin' => 'ROLE_ADMIN',
                    'Super Admin' => 'ROLE_SUPER_ADMIN'
                ])
                ->allowMultipleChoices()
                ->renderExpanded(false)
                ->renderAsBadges([
                    'ROLE_USER' => 'primary',
                    'ROLE_MODERATOR' => 'warning',
                    'ROLE_ADMIN' => 'danger',
                    'ROLE_SUPER_ADMIN' => 'dark'
                ]),
            NumberField::new('trustScore')
                ->setNumDecimals(1)
                ->setTemplatePath('admin/fields/trust_score.html.twig'),
        ];

        if ($pageName === Crud::PAGE_DETAIL || $pageName === Crud::PAGE_INDEX) {
            $fields[] = DateTimeField::new('createdAt')->setFormat('dd/MM/yyyy HH:mm');
            $fields[] = DateTimeField::new('updatedAt')->setFormat('dd/MM/yyyy HH:mm')->hideOnIndex();
        }

        if ($pageName === Crud::PAGE_DETAIL) {
            $fields[] = AssociationField::new('profile')
                ->setTemplatePath('admin/fields/user_profile.html.twig');
            $fields[] = CollectionField::new('properties')
                ->setTemplatePath('admin/fields/user_properties.html.twig');
            $fields[] = CollectionField::new('documents')
                ->setTemplatePath('admin/fields/user_documents.html.twig');
            $fields[] = NumberField::new('profile.completionPercentage', 'Profile Completion')
                ->setNumDecimals(0)
                ->setSuffix('%');
        }

        if ($pageName === Crud::PAGE_INDEX) {
            $fields[] = TextField::new('profile', 'First Name')
                ->formatValue(fn ($value, $entity) => $entity->getProfile()?->getFirstName());

            $fields[] = TextField::new('profile', 'Last Name')
                ->formatValue(fn ($value, $entity) => $entity->getProfile()?->getLastName());
            $fields[] = BooleanField::new('profile.personalInfoValidated', 'Profile')
                ->renderAsSwitch(false);
            $fields[] = BooleanField::new('profile.identityValidated', 'Identity')
                ->renderAsSwitch(false);
            $fields[] = BooleanField::new('profile.financialValidated', 'Financial')
                ->renderAsSwitch(false);
        }

        return $fields;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(BooleanFilter::new('isVerified', 'Email Verified'))
            ->add(ChoiceFilter::new('roles')->setChoices([
                'User' => 'ROLE_USER',
                'Moderator' => 'ROLE_MODERATOR',
                'Admin' => 'ROLE_ADMIN',
                'Super Admin' => 'ROLE_SUPER_ADMIN'
            ]))
            ->add(NumericFilter::new('trustScore'))
            ->add(DateTimeFilter::new('createdAt'));
//            ->add(BooleanFilter::new('profile.personalInfoValidated', 'Profile Complete'))
//            ->add(BooleanFilter::new('profile.identityValidated', 'Identity Verified'))
//            ->add(BooleanFilter::new('profile.financialValidated', 'Financial Verified'));
    }

    public function viewProfile(AdminContext $context): Response
    {
        $user = $context->getEntity()->getInstance();

        return $this->render('admin/user/profile.html.twig', [
            'user' => $user,
            'profile' => $user->getProfile(),
            'trustScoreBreakdown' => $this->get('App\Service\User\TrustScoreCalculator')->getScoreBreakdown($user)
        ]);
    }

    public function viewDocuments(AdminContext $context): Response
    {
        $user = $context->getEntity()->getInstance();

        return $this->render('admin/user/documents.html.twig', [
            'user' => $user,
            'documents' => $user->getDocuments()
        ]);
    }

    public function sendEmail(AdminContext $context): Response
    {
        $user = $context->getEntity()->getInstance();

        // Handle email sending form
        if ($this->isPostRequest()) {
            $subject = $this->getRequest()->request->get('subject');
            $content = $this->getRequest()->request->get('content');

            $this->get('App\Service\Email\EmailService')->sendRawEmail(
                $user->getEmail(),
                $subject,
                strip_tags($content),
                $content
            );

            $this->addFlash('success', 'Email sent successfully to ' . $user->getEmail());

            return $this->redirectToRoute('admin', [
                'crudAction' => 'detail',
                'crudControllerFqcn' => self::class,
                'entityId' => $user->getId()
            ]);
        }

        return $this->render('admin/user/send_email.html.twig', [
            'user' => $user
        ]);
    }

    private function isPostRequest(): bool
    {
        return $this->getRequest()->isMethod('POST');
    }

    private function getRequest()
    {
        return $this->container->get('request_stack')->getCurrentRequest();
    }
}
