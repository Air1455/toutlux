<?php

namespace App\Controller\Admin;

use App\Entity\Document;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

class DocumentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Document::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Document')
            ->setEntityLabelInPlural('Documents')
            ->setPageTitle('index', 'Document Management')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('user'),
            ChoiceField::new('type')->setChoices([
                'Identity' => Document::TYPE_IDENTITY,
                'Selfie' => Document::TYPE_SELFIE,
                'Financial' => Document::TYPE_FINANCIAL,
            ]),
            ChoiceField::new('status')->setChoices([
                'Pending' => Document::STATUS_PENDING,
                'Approved' => Document::STATUS_APPROVED,
                'Rejected' => Document::STATUS_REJECTED,
            ])->renderAsBadges([
                Document::STATUS_PENDING => 'warning',
                Document::STATUS_APPROVED => 'success',
                Document::STATUS_REJECTED => 'danger',
            ]),
            DateTimeField::new('createdAt')->setFormat('dd/MM/yyyy HH:mm'),
            TextField::new('fileName')->hideOnIndex(),
            AssociationField::new('validatedBy')->hideOnIndex(),
            DateTimeField::new('validatedAt')->hideOnIndex(),
            TextField::new('rejectionReason')->hideOnIndex(),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('user'))
            ->add(ChoiceFilter::new('type')->setChoices([
                'Identity' => Document::TYPE_IDENTITY,
                'Selfie' => Document::TYPE_SELFIE,
                'Financial' => Document::TYPE_FINANCIAL,
            ]))
            ->add(ChoiceFilter::new('status')->setChoices([
                'Pending' => Document::STATUS_PENDING,
                'Approved' => Document::STATUS_APPROVED,
                'Rejected' => Document::STATUS_REJECTED,
            ]));
    }
}
