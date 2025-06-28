<?php

namespace App\EventListener;

use App\Event\MessageCreatedEvent;
use App\Service\Messaging\EmailService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: 'message.created')]
class MessageListener
{
    public function __construct(
        private EmailService $emailService
    ) {}

    public function __invoke(MessageCreatedEvent $event): void
    {
        $message = $event->getMessage();

        // Si c'est un message utilisateur vers admin, envoyer notification email
        if ($message->getType() === 'user_to_admin') {
            $this->emailService->sendNewMessageNotification($message);
        }
    }
}
