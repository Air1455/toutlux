<?php

namespace App\EventListener;

use App\Entity\User;
use App\Service\Messaging\EmailService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Psr\Log\LoggerInterface;

#[AsDoctrineListener(event: Events::prePersist, priority: 500, connection: 'default')]
#[AsDoctrineListener(event: Events::preUpdate, priority: 500, connection: 'default')]
#[AsDoctrineListener(event: Events::postUpdate, priority: 400, connection: 'default')]
readonly class UserListener
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private EmailService $emailService,
        private LoggerInterface $logger,
    ) {}

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof User) {
            return;
        }

        // Hash du mot de passe
        $this->hashPassword($entity);

        $this->logger->info("User created", [
            'user_id' => $entity->getId(),
            'email' => $entity->getEmail(),
        ]);
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof User) {
            return;
        }

        // 1. Gestion du mot de passe
        if ($args->hasChangedField('password')) {
            $this->hashPassword($entity);
            $args->setNewValue('password', $entity->getPassword());

            $this->logger->info("Password changed", [
                'user_id' => $entity->getId()
            ]);
        }

        // 2. Gestion du changement d'email
        if ($args->hasChangedField('email')) {
            $oldEmail = $args->getOldValue('email');
            $newEmail = $args->getNewValue('email');

            if ($oldEmail !== $newEmail) {
                // Réinitialiser la vérification email
                $entity->setIsEmailVerified(false);
                $entity->setEmailVerifiedAt(null);
                $entity->setEmailConfirmationToken(null);
                $entity->setEmailConfirmationTokenExpiresAt(null);
                $entity->setEmailVerificationAttempts(0);

                // Auto-vérification Gmail
                if ($entity->isGmailAccount()) {
                    $entity->setIsEmailVerified(true);
                    $entity->setEmailVerifiedAt(new \DateTimeImmutable());
                }

                $this->logger->info("Email changed", [
                    'user_id' => $entity->getId(),
                    'old_email' => $oldEmail,
                    'new_email' => $newEmail,
                    'auto_verified' => $entity->isGmailAccount()
                ]);
            }
        }

        // updatedAt est géré automatiquement par @ORM\PreUpdate dans l'entité
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof User) {
            return;
        }

        $changeSet = $args->getObjectManager()
            ->getUnitOfWork()
            ->getEntityChangeSet($entity);

        // Envoyer email de confirmation si email changé (et pas Gmail)
        if (isset($changeSet['email'])) {
            $oldEmail = $changeSet['email'][0];
            $newEmail = $changeSet['email'][1];

            if ($oldEmail !== $newEmail && !$entity->isGmailAccount()) {
                try {
                    $this->emailService->sendEmailConfirmation($entity);

                    $this->logger->info("Email confirmation sent", [
                        'user_id' => $entity->getId(),
                        'new_email' => $newEmail
                    ]);
                } catch (\Exception $e) {
                    $this->logger->error("Failed to send email confirmation", [
                        'user_id' => $entity->getId(),
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }

    private function hashPassword(User $user): void
    {
        $plainPassword = $user->getPassword();

        if ($plainPassword && !$this->isPasswordHashed($plainPassword)) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
        }
    }

    private function isPasswordHashed(string $password): bool
    {
        return str_starts_with($password, '$2y$') ||
            str_starts_with($password, '$argon2') ||
            str_starts_with($password, '$2b$');
    }
}
