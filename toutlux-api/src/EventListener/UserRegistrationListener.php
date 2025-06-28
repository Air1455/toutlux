<?php

namespace App\EventListener;

use App\Event\UserRegisteredEvent;
use App\Service\User\UserWorkflowService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: 'user.registered')]
class UserRegistrationListener
{
    public function __construct(
        private UserWorkflowService $userWorkflowService
    ) {}

    public function __invoke(UserRegisteredEvent $event): void
    {
        $this->userWorkflowService->handleUserRegistration($event->getUser());
    }
}
