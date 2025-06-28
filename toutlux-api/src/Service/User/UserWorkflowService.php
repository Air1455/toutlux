<?php

namespace App\Service\User;

use App\Entity\User;
use App\Enum\UserStatus;
use App\Service\Messaging\EmailService;
use App\Service\Messaging\MessageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\Event\UserStatusChangedEvent;

class UserWorkflowService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EmailService $emailService,
        private MessageService $messageService,
        private EventDispatcherInterface $eventDispatcher
    ) {}

    public function handleUserRegistration(User $user): void
    {
        // Créer message de bienvenue
        $this->messageService->createMessage(
            $user,
            'Bienvenue sur notre plateforme',
            'Votre compte a été créé avec succès. Veuillez confirmer votre email pour activer votre compte.',
            \App\Enum\MessageType::SYSTEM
        );

        // Envoyer email de bienvenue
        $this->emailService->sendWelcomeEmail($user);
    }

    public function handleEmailConfirmation(User $user): void
    {
        $user->setIsEmailVerified(true);
        $user->setStatus(UserStatus::EMAIL_CONFIRMED->value);

        $this->entityManager->flush();

        // Message système
        $this->messageService->createMessage(
            $user,
            'Email confirmé',
            'Votre adresse email a été confirmée avec succès. Vous pouvez maintenant soumettre vos documents.',
            \App\Enum\MessageType::SYSTEM
        );

        // Email de confirmation
        $this->emailService->sendEmailConfirmedNotification($user);

        // Dispatch event
        $this->eventDispatcher->dispatch(
            new UserStatusChangedEvent($user, UserStatus::EMAIL_CONFIRMED),
            'user.status_changed'
        );
    }

    public function handleDocumentsApproval(User $user): void
    {
        $user->setIsIdentityVerified(true);
        $user->setIsFinancialDocsVerified(true);
        $user->setStatus(UserStatus::DOCUMENTS_APPROVED->value);

        $this->entityManager->flush();

        // Message système
        $this->messageService->createMessage(
            $user,
            'Documents approuvés',
            'Vos documents ont été vérifiés et approuvés. Votre compte est maintenant actif.',
            \App\Enum\MessageType::SYSTEM
        );

        // Email de notification
        $this->emailService->sendDocumentsApprovedNotification($user);

        // Dispatch event
        $this->eventDispatcher->dispatch(
            new UserStatusChangedEvent($user, UserStatus::DOCUMENTS_APPROVED),
            'user.status_changed'
        );
    }
}
