<?php

namespace App\EventSubscriber;

use App\Entity\Document;
use App\Entity\User;
use App\Service\Document\TrustScoreCalculator;
use App\Service\Email\NotificationEmailService;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Bundle\SecurityBundle\Security;

class DocumentSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private TrustScoreCalculator $trustScoreCalculator,
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

        if (!$entity instanceof Document) {
            return;
        }

        // Définir l'utilisateur si non défini
        if (!$entity->getUser() && $this->security->getUser() instanceof User) {
            $entity->setUser($this->security->getUser());
        }

        // Vérifier si le document expire bientôt
        if ($entity->getExpiresAt()) {
            $daysUntilExpiration = $entity->getDaysUntilExpiration();
            if ($daysUntilExpiration !== null && $daysUntilExpiration <= 0) {
                $entity->markAsExpired();
            }
        }
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Document) {
            return;
        }

        // Notifier l'admin qu'un nouveau document est à valider
        if ($entity->getStatus() === \App\Enum\DocumentStatus::PENDING) {
            $this->notificationService->notifyAdminNewDocument($entity);
        }
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Document) {
            return;
        }

        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();
        $changeset = $uow->getEntityChangeSet($entity);

        // Si le statut a changé
        if (isset($changeset['status'])) {
            $oldStatus = $changeset['status'][0];
            $newStatus = $changeset['status'][1];

            // Si le document a été validé ou rejeté
            if ($oldStatus === \App\Enum\DocumentStatus::PENDING) {
                if ($newStatus === \App\Enum\DocumentStatus::APPROVED) {
                    // Recalculer le score de confiance
                    $this->trustScoreCalculator->updateUserTrustScore($entity->getUser());

                    // Notifier l'utilisateur
                    $this->notificationService->notifyDocumentValidation($entity, true);
                } elseif ($newStatus === \App\Enum\DocumentStatus::REJECTED) {
                    // Notifier l'utilisateur
                    $this->notificationService->notifyDocumentValidation($entity, false);
                }
            }
        }

        // Vérifier l'expiration
        if ($entity->getExpiresAt() && $entity->needsRenewal(30)) {
            // Notifier l'utilisateur que le document expire bientôt
            $this->notificationService->createNotification(
                $entity->getUser(),
                'document_expiring_soon',
                'Document bientôt expiré',
                sprintf(
                    'Votre document "%s" expire dans %d jours. Pensez à le renouveler.',
                    $entity->getTitle() ?: $entity->getType()->label(),
                    $entity->getDaysUntilExpiration()
                ),
                ['document_id' => $entity->getId()]
            );
        }
    }
}
