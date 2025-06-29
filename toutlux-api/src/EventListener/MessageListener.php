<?php

namespace App\EventListener;

use App\Event\MessageCreatedEvent;
use App\Enum\MessageType;
use App\Service\Messaging\EmailService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Listener pour l'envoi automatique des notifications lors de la création de messages.
 * - Notifie l'admin si un utilisateur envoie un message
 * - Notifie l'utilisateur si l'admin répond
 */
#[AsEventListener(event: 'message.created')]
class MessageListener
{
    public function __construct(
        private EmailService $emailService
    ) {}

    public function __invoke(MessageCreatedEvent $event): void
    {
        $message = $event->getMessage();

        // Notification admin si message user->admin
        if ($message->getType() === MessageType::USER_TO_ADMIN) {
            $this->emailService->sendNewMessageNotificationToAdmin($message);
        }

        // Notification utilisateur si message admin->user
        if ($message->getType() === MessageType::ADMIN_TO_USER) {
            $this->emailService->sendAdminReply($message, $message->getContent());
        }
    }
}
