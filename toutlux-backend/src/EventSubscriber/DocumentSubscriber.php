<?php

namespace App\EventSubscriber;

use App\Entity\Document;
use App\Entity\User;
use App\Enum\DocumentStatus;
use App\Enum\NotificationType;
use App\Service\Document\TrustScoreCalculator;
use App\Service\Email\NotificationEmailService;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Bundle\SecurityBundle\Security;

class DocumentSubscriber implements EventSubscriber
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

        if (!$entity->getUser() && $this->security->getUser() instanceof User) {
            $entity->setUser($this->security->getUser());
        }

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

        if ($entity->getStatus() === DocumentStatus::PENDING) {
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

        if (isset($changeset['status'])) {
            [$oldStatus, $newStatus] = $changeset['status'];

            if (
                $oldStatus instanceof DocumentStatus &&
                $newStatus instanceof DocumentStatus &&
                $oldStatus === DocumentStatus::PENDING
            ) {
                if ($newStatus === DocumentStatus::APPROVED) {
                    $this->trustScoreCalculator->updateUserTrustScore($entity->getUser());
                    $this->notificationService->notifyDocumentValidation($entity, true);
                } elseif ($newStatus === DocumentStatus::REJECTED) {
                    $this->notificationService->notifyDocumentValidation($entity, false);
                }
            }
        }

        if ($entity->getExpiresAt() && $entity->needsRenewal(30)) {
            $this->notificationService->createNotification(
                $entity->getUser(),
                NotificationType::DOCUMENT_EXPIRING_SOON,
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
