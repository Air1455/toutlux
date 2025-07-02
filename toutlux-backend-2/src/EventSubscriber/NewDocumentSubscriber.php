<?php

namespace App\EventSubscriber;

use App\Entity\Document;
use App\Service\Document\DocumentValidationService;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class NewDocumentSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private DocumentValidationService $documentValidationService
    ) {}

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Document) {
            return;
        }

        // Submit document for validation (notifies admins)
        $this->documentValidationService->submitDocument($entity);
    }
}
