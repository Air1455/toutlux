<?php

namespace App\EventSubscriber;

use App\Entity\Message;
use App\Entity\User;
use App\Service\Email\NotificationEmailService;
use App\Service\Message\MessageValidationService;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\Security;

class MessageSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private MessageValidationService $validationService,
        private NotificationEmailService $notificationService
    ) {}

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::postPersist,
            Events::postUpdate,
        ];
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Message) {
            return;
        }

        // Définir l'expéditeur si non défini
        if (!$entity->getSender() && $this->security->getUser() instanceof User) {
            $entity->setSender($this->security->getUser());
        }

        // Valider et nettoyer le contenu
        $content = $entity->getContent();
        $cleanContent = $this->validationService->sanitizeContent($content);
        $entity->setContent($cleanContent);

        // Analyser le message pour déterminer s'il nécessite une modération
        $validation = $this->validationService->validateMessage($cleanContent);
        if ($validation['requires_moderation']) {
            $entity->setNeedsModeration(true);
            $entity->setStatus(\App\Enum\MessageStatus::PENDING);
        }
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Message) {
            return;
        }

        // Si le message nécessite une modération, notifier l'admin
        if ($entity->getNeedsModeration()) {
            $this->notificationService->notifyAdminMessageToModerate($entity);
        } else {
            // Sinon, notifier directement le destinataire
            $this->notificationService->notifyNewMessage($entity);
        }
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Message) {
            return;
        }

        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();
        $changeset = $uow->getEntityChangeSet($entity);

        // Si le statut a changé
        if (isset($changeset['status'])) {
            $oldStatus = $changeset['status'][0];
            $newStatus = $changeset['status'][1];

            // Si le message a été approuvé
            if ($oldStatus === \App\Enum\MessageStatus::PENDING &&
                $newStatus === \App\Enum\MessageStatus::APPROVED) {
                // Notifier le destinataire
                $this->notificationService->notifyNewMessage($entity);
            }

            // Si le message a été rejeté
            if ($oldStatus === \App\Enum\MessageStatus::PENDING &&
                $newStatus === \App\Enum\MessageStatus::REJECTED) {
                // Notifier l'expéditeur
                $this->notificationService->createNotification(
                    $entity->getSender(),
                    'message_rejected',
                    'Message rejeté',
                    sprintf(
                        'Votre message à %s a été rejeté par la modération. Motif : %s',
                        $entity->getRecipient()->getFullName(),
                        $entity->getModerationReason() ?: 'Contenu inapproprié'
                    ),
                    [
                        'message_id' => $entity->getId(),
                        'recipient_id' => $entity->getRecipient()->getId()
                    ]
                );
            }
        }

        // Si le message a été lu
        if (isset($changeset['isRead']) && $changeset['isRead'][1] === true) {
            // On pourrait notifier l'expéditeur que son message a été lu
            // mais ce n'est pas forcément souhaitable pour la privacy
        }
    }
}
