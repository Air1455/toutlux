<?php

namespace App\EventListener;

use App\Event\UserStatusChangedEvent;
use App\Enum\UserStatus;
use App\Service\User\UserWorkflowService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: 'user.status_changed')]
class UserStatusChangedListener
{
    public function __construct(
        private UserWorkflowService $userWorkflowService
    ) {}

    public function __invoke(UserStatusChangedEvent $event): void
    {
        match($event->getNewStatus()) {
            UserStatus::EMAIL_CONFIRMED => $this->handleEmailConfirmed($event),
            UserStatus::DOCUMENTS_APPROVED => $this->handleDocumentsApproved($event),
            default => null
        };
    }

    private function handleEmailConfirmed(UserStatusChangedEvent $event): void
    {
        // Logique additionnelle si nécessaire
    }

    private function handleDocumentsApproved(UserStatusChangedEvent $event): void
    {
        // Logique additionnelle si nécessaire
    }
}
