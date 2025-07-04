<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\Document\TrustScoreCalculator;
use App\Service\Email\WelcomeEmailService;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\EventDispatcher\EventSubscriberInterface as SymfonyEventSubscriberInterface;
use Symfony\Component\Security\Core\Event\AuthenticationSuccessEvent;

class UserSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TrustScoreCalculator $trustScoreCalculator,
        private WelcomeEmailService $welcomeEmailService
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
        // Note: Ceci pourrait être mieux géré via un message asynchrone
        try {
            $this->welcomeEmailService->sendWelcomeEmail($entity);
        } catch (\Exception $e) {
            // Logger l'erreur mais ne pas faire échouer la création de l'utilisateur
            // Le logger devrait être injecté pour cela
        }
    }
}
