<?php

namespace App\MessageHandler;

use App\Entity\Notification;
use App\Message\SendNotificationMessage;
use App\Service\Email\NotificationEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SendNotificationMessageHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationEmailService $emailService,
        private LoggerInterface $logger
    ) {}

    public function __invoke(SendNotificationMessage $message): void
    {
        $notification = $this->entityManager
            ->getRepository(Notification::class)
            ->find($message->getNotificationId());

        if (!$notification) {
            $this->logger->error('Notification not found', [
                'id' => $message->getNotificationId()
            ]);
            return;
        }

        try {
            // Envoyer l'email correspondant selon le type de notification
            $this->emailService->sendNotificationEmail($notification);

            $this->logger->info('Notification email sent', [
                'notification_id' => $notification->getId(),
                'user_id' => $notification->getUser()->getId()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send notification email', [
                'notification_id' => $notification->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }
}
