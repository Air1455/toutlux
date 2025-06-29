<?php

namespace App\Controller\Admin;

use App\Entity\EmailLog;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class EmailLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return EmailLog::class;
    }

    
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('toEmail'),
            TextField::new('subject'),
            TextField::new('template'),
            TextareaField::new('templateData')->hideOnIndex(),
            ChoiceField::new('status')->setChoices([
                'Pending' => 'pending',
                'Sent' => 'sent',
                'Failed' => 'failed',
            ]),
            TextareaField::new('errorMessage')->hideOnIndex(),
            AssociationField::new('user'),
            AssociationField::new('message'),
            DateTimeField::new('createdAt'),
            DateTimeField::new('sentAt'),
        ];
    }
    
}
