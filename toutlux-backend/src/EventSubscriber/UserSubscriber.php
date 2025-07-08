<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\Email\WelcomeEmailService;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Psr\Log\LoggerInterface;

class UserSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private WelcomeEmailService $welcomeEmailService,
        private LoggerInterface $logger
    ) {}

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
            Events::postPersist
        ];
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof User) {
            return;
        }

        // Vérifier la complétion du profil
        $entity->checkProfileCompletion();

        // Calculer le score de confiance initial
        $entity->calculateTrustScore();
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof User) {
            return;
        }

        // Vérifier la complétion du profil
        $entity->checkProfileCompletion();

        // Recalculer le score de confiance
        $entity->calculateTrustScore();
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof User) {
            return;
        }

        // Envoyer l'email de bienvenue pour les nouveaux utilisateurs
        try {
            $this->welcomeEmailService->sendWelcomeEmail($entity);
        } catch (\Exception $e) {
            // Logger l'erreur mais ne pas faire échouer la création de l'utilisateur
            $this->logger->error('Failed to send welcome email', [
                'user_id' => $entity->getId(),
                'user_email' => $entity->getEmail(),
                'error' => $e->getMessage()
            ]);
        }
    }
}
