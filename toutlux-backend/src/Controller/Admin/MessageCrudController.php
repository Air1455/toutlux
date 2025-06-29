<?php

namespace App\Controller\Admin;

use App\Entity\Message;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class MessageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Message::class;
    }

    
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('subject'),
            TextareaField::new('content'),
            ChoiceField::new('type')->setChoices([
                'System' => 'system',
                'User to Admin' => 'user_to_admin',
                'Admin to User' => 'admin_to_user',
            ]),
            ChoiceField::new('status')->setChoices([
                'Unread' => 'unread',
                'Read' => 'read',
                'Archived' => 'archived',
            ]),
            BooleanField::new('isRead'),
            BooleanField::new('emailSent'),
            AssociationField::new('user'),
            DateTimeField::new('createdAt')->hideOnForm(),
            DateTimeField::new('readAt'),
        ];
    }
    
}
