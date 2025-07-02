<?php

namespace App\Controller\Admin;

use App\Entity\Message;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

class MessageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Message::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Message')
            ->setEntityLabelInPlural('Messages')
            ->setPageTitle('index', 'Message Management')
            ->setPageTitle('detail', fn (Message $message) => $message->getSubject())
            ->setSearchFields(['subject', 'content', 'sender.email', 'recipient.email'])
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        $moderate = Action::new('moderate', 'Moderate', 'fa fa-check')
            ->linkToRoute('admin_message_moderate', function (Message $message) {
                return ['id' => $message->getId()];
            })
            ->displayIf(fn (Message $message) => $message->getStatus() === Message::STATUS_PENDING);

        $viewConversation = Action::new('viewConversation', 'View Conversation', 'fa fa-comments')
            ->linkToCrudAction('viewConversation');

        return $actions
            ->add(Crud::PAGE_INDEX, $moderate)
            ->add(Crud::PAGE_DETAIL, $moderate)
            ->add(Crud::PAGE_DETAIL, $viewConversation)
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->displayIf(fn (Message $message) =>
                    $message->getStatus() !== Message::STATUS_PENDING
                );
            });
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('sender')
                ->formatValue(function ($value, $entity) {
                    return sprintf('%s (%s)',
                        $entity->getSender()->getFullName() ?? 'N/A',
                        $entity->getSender()->getEmail()
                    );
                }),
            AssociationField::new('recipient')
                ->formatValue(function ($value, $entity) {
                    return sprintf('%s (%s)',
                        $entity->getRecipient()->getFullName() ?? 'N/A',
                        $entity->getRecipient()->getEmail()
                    );
                }),
            TextField::new('subject'),
            TextareaField::new('content')
                ->hideOnIndex()
                ->setMaxLength(500),
            ChoiceField::new('status')
                ->setChoices([
                    'Pending' => Message::STATUS_PENDING,
                    'Approved' => Message::STATUS_APPROVED,
                    'Rejected' => Message::STATUS_REJECTED,
                ])
                ->renderAsBadges([
                    Message::STATUS_PENDING => 'warning',
                    Message::STATUS_APPROVED => 'success',
                    Message::STATUS_REJECTED => 'danger',
                ]),
            BooleanField::new('isRead', 'Read')
                ->renderAsSwitch(false),
            AssociationField::new('property')
                ->hideOnIndex(),
            DateTimeField::new('createdAt')
                ->setFormat('dd/MM/yyyy HH:mm')
                ->hideOnForm(),
        ];

        if ($pageName === Crud::PAGE_DETAIL) {
            $fields[] = TextareaField::new('moderatedContent')
                ->setLabel('Moderated Content')
                ->hideOnForm();
            $fields[] = DateTimeField::new('moderatedAt')
                ->setFormat('dd/MM/yyyy HH:mm')
                ->hideOnForm();
            $fields[] = AssociationField::new('moderatedBy')
                ->hideOnForm();
        }

        return $fields;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status')->setChoices([
                'Pending' => Message::STATUS_PENDING,
                'Approved' => Message::STATUS_APPROVED,
                'Rejected' => Message::STATUS_REJECTED,
            ]))
            ->add(BooleanFilter::new('isRead'))
            ->add(EntityFilter::new('sender'))
            ->add(EntityFilter::new('recipient'))
            ->add(EntityFilter::new('property'))
            ->add(DateTimeFilter::new('createdAt'));
    }
}
