<?php

namespace App\Service\Email;

use App\Entity\Document;
use App\Entity\Message;
use App\Entity\Notification;
use App\Entity\Property;
use App\Entity\User;
use App\Enum\NotificationType;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;

class NotificationEmailService
{
    public function __construct(
        private EmailService $emailService,
        private EntityManagerInterface $entityManager,
        private NotificationRepository $notificationRepository
    ) {}

    /**
     * Créer une notification in-app et optionnellement envoyer un email
     */
    public function createNotification(
        User $user,
        NotificationType $type,
        string $title,
        string $message,
        array $data = [],
        bool $sendEmail = true
    ): Notification {
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setType($type);
        $notification->setTitle($title);
        $notification->setMessage($message);
        $notification->setData($data);
        $notification->setIsRead(false);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        if ($sendEmail && $user->isEmailNotificationsEnabled()) {
            $this->sendNotificationEmail($notification);
        }

        return $notification;
    }

    /**
     * Envoyer un email de notification
     */
    public function sendNotificationEmail(Notification $notification): void
    {
        $user = $notification->getUser();
        $context = $this->emailService->createEmailContext([
            'user' => $user,
            'notification' => $notification,
            'firstName' => $user->getFirstName() ?: 'Cher utilisateur',
            'actionUrl' => $this->getActionUrl($notification),
            'unsubscribeUrl' => $_ENV['APP_URL'] . '/settings/notifications'
        ]);

        $this->emailService->sendEmail(
            $user->getEmail(),
            $notification->getTitle(),
            'emails/notification.html.twig',
            $context
        );
    }

    /**
     * Notification de validation de document
     */
    public function notifyDocumentValidation(Document $document, bool $isValidated): void
    {
        $user = $document->getUser();
        $type = $isValidated ? NotificationType::DOCUMENT_VALIDATED : NotificationType::DOCUMENT_REJECTED;

        $title = $isValidated
            ? 'Document validé'
            : 'Document refusé';

        $message = $isValidated
            ? sprintf('Votre document "%s" a été validé avec succès.', $document->getFileName())
            : sprintf('Votre document "%s" a été refusé. Motif : %s', $document->getFileName(), $document->getRejectionReason());

        $this->createNotification(
            $user,
            $type,
            $title,
            $message,
            [
                'document_id' => $document->getId(),
                'document_type' => $document->getType(),
                'rejection_reason' => $document->getRejectionReason()
            ]
        );

        // Email spécifique pour la validation de document
        $template = $isValidated
            ? 'emails/document_validated.html.twig'
            : 'emails/document_rejected.html.twig';

        $context = $this->emailService->createEmailContext([
            'user' => $user,
            'document' => $document,
            'firstName' => $user->getFirstName() ?: 'Cher utilisateur',
            'profileUrl' => $_ENV['APP_URL'] . '/profile/documents'
        ]);

        $this->emailService->sendEmail(
            $user->getEmail(),
            $title . ' - TOUTLUX',
            $template,
            $context
        );
    }

    /**
     * Notification de nouveau message
     */
    public function notifyNewMessage(Message $message): void
    {
        $recipient = $message->getRecipient();

        $this->createNotification(
            $recipient,
            NotificationType::NEW_MESSAGE,
            'Nouveau message',
            sprintf('Vous avez reçu un nouveau message de %s', $message->getSender()->getFullName()),
            [
                'message_id' => $message->getId(),
                'sender_id' => $message->getSender()->getId(),
                'property_id' => $message->getProperty() ? $message->getProperty()->getId() : null
            ]
        );

        // Email spécifique pour nouveau message
        $context = $this->emailService->createEmailContext([
            'user' => $recipient,
            'message' => $message,
            'sender' => $message->getSender(),
            'property' => $message->getProperty(),
            'messageUrl' => $_ENV['APP_URL'] . '/inbox/' . $message->getId()
        ]);

        $this->emailService->sendEmail(
            $recipient->getEmail(),
            'Nouveau message de ' . $message->getSender()->getFullName(),
            'emails/new_message.html.twig',
            $context
        );
    }

    /**
     * Notification à l'admin
     */
    public function notifyAdmin(string $subject, string $message, array $data = []): void
    {
        $context = $this->emailService->createEmailContext([
            'subject' => $subject,
            'message' => $message,
            'data' => $data,
            'adminUrl' => $_ENV['APP_URL'] . '/admin'
        ]);

        $this->emailService->sendAdminNotification(
            $subject,
            'emails/admin_notification.html.twig',
            $context
        );
    }

    /**
     * Notification de nouveau document à valider
     */
    public function notifyAdminNewDocument(Document $document): void
    {
        $user = $document->getUser();

        $this->notifyAdmin(
            'Nouveau document à valider',
            sprintf(
                'Un nouveau document de type "%s" a été soumis par %s et nécessite votre validation.',
                $document->getType(),
                $user->getFullName()
            ),
            [
                'document_id' => $document->getId(),
                'document_type' => $document->getType(),
                'user_id' => $user->getId(),
                'user_name' => $user->getFullName(),
                'user_email' => $user->getEmail(),
                'validation_url' => $_ENV['APP_URL'] . '/admin/documents/validate/' . $document->getId()
            ]
        );
    }

    /**
     * Notification de nouveau message à modérer
     */
    public function notifyAdminMessageToModerate(Message $message): void
    {
        $this->notifyAdmin(
            'Nouveau message à modérer',
            sprintf(
                'Un message de %s vers %s nécessite votre modération avant envoi.',
                $message->getSender()->getFullName(),
                $message->getRecipient()->getFullName()
            ),
            [
                'message_id' => $message->getId(),
                'sender_name' => $message->getSender()->getFullName(),
                'recipient_name' => $message->getRecipient()->getFullName(),
                'property_title' => $message->getProperty() ? $message->getProperty()->getTitle() : 'N/A',
                'moderation_url' => $_ENV['APP_URL'] . '/admin/messages/moderate/' . $message->getId()
            ]
        );
    }

    /**
     * Notification de changement de statut de propriété
     */
    public function notifyPropertyStatusChange(Property $property, string $oldStatus, string $newStatus): void
    {
        $owner = $property->getOwner();

        $statusLabels = [
            'draft' => 'Brouillon',
            'published' => 'Publiée',
            'sold' => 'Vendue',
            'rented' => 'Louée',
            'archived' => 'Archivée'
        ];

        $title = sprintf('Changement de statut : %s', $property->getTitle());
        $message = sprintf(
            'Le statut de votre propriété "%s" est passé de "%s" à "%s".',
            $property->getTitle(),
            $statusLabels[$oldStatus] ?? $oldStatus,
            $statusLabels[$newStatus] ?? $newStatus
        );

        $this->createNotification(
            $owner,
            NotificationType::PROPERTY_STATUS_CHANGE,
            $title,
            $message,
            [
                'property_id' => $property->getId(),
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ]
        );
    }

    /**
     * Récupérer l'URL d'action pour une notification
     */
    private function getActionUrl(Notification $notification): ?string
    {
        $baseUrl = $_ENV['APP_URL'];
        $data = $notification->getData();

        switch ($notification->getType()) {
            case 'new_message':
                return $baseUrl . '/inbox/' . ($data['message_id'] ?? '');

            case 'document_validated':
            case 'document_rejected':
                return $baseUrl . '/profile/documents';

            case 'property_status_change':
                return $baseUrl . '/properties/' . ($data['property_id'] ?? '');

            case 'welcome':
                return $data['action_url'] ?? $baseUrl . '/profile';

            default:
                return $baseUrl;
        }
    }

    /**
     * Marquer des notifications comme lues
     */
    public function markAsRead(array $notificationIds, User $user): void
    {
        $notifications = $this->notificationRepository->findBy([
            'id' => $notificationIds,
            'user' => $user
        ]);

        foreach ($notifications as $notification) {
            $notification->setIsRead(true);
            $notification->setReadAt(new \DateTimeImmutable());
        }

        $this->entityManager->flush();
    }

    /**
     * Récupérer les notifications non lues
     */
    public function getUnreadNotifications(User $user, int $limit = 10): array
    {
        return $this->notificationRepository->findBy(
            ['user' => $user, 'isRead' => false],
            ['createdAt' => 'DESC'],
            $limit
        );
    }

    /**
     * Compter les notifications non lues
     */
    public function countUnreadNotifications(User $user): int
    {
        return $this->notificationRepository->count([
            'user' => $user,
            'isRead' => false
        ]);
    }
}
