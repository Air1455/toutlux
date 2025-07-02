<?php

namespace App\Service\Message;

use App\Entity\Message;
use App\Entity\User;
use App\Entity\Notification;
use App\Service\Notification\NotificationService;
use App\Service\Email\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class MessageModerationService
{
    // Words/phrases that might need moderation
    private const MODERATION_PATTERNS = [
        '/\b(?:phone|tel|téléphone)\s*:?\s*\d{10,}/i',  // Phone numbers
        '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', // Email addresses
        '/\b(?:06|07|09)\s?\d{2}\s?\d{2}\s?\d{2}\s?\d{2}\b/', // French mobile numbers
        '/(?:http|https):\/\/[^\s]+/', // URLs
    ];

    // Spam indicators
    private const SPAM_KEYWORDS = [
        'viagra', 'casino', 'lottery', 'winner', 'click here',
        'limited offer', 'act now', 'free money'
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationService $notificationService,
        private EmailService $emailService,
        private LoggerInterface $logger
    ) {}

    public function submitForModeration(Message $message): void
    {
        // Check if message needs moderation
        if (!$message->needsModeration()) {
            // Direct messages between users don't need moderation
            $this->approveMessage($message);
            return;
        }

        // Perform automatic checks
        $moderationResult = $this->performAutomaticModeration($message);

        if ($moderationResult['requiresManualReview']) {
            // Notify admins
            $this->notifyAdminsOfPendingMessage($message);
            $this->logger->info('Message submitted for manual moderation', [
                'messageId' => $message->getId(),
                'reason' => $moderationResult['reason']
            ]);
        } else if ($moderationResult['autoApproved']) {
            // Auto-approve clean messages
            $this->approveMessage($message);
        } else {
            // Auto-reject spam
            $message->reject($this->getSystemUser());
            $this->entityManager->flush();

            $this->logger->warning('Message auto-rejected as spam', [
                'messageId' => $message->getId(),
                'reason' => $moderationResult['reason']
            ]);
        }
    }

    private function performAutomaticModeration(Message $message): array
    {
        $content = $message->getContent();
        $subject = $message->getSubject();
        $fullText = $subject . ' ' . $content;

        // Check for spam
        $spamScore = $this->calculateSpamScore($fullText);
        if ($spamScore > 0.7) {
            return [
                'requiresManualReview' => false,
                'autoApproved' => false,
                'reason' => 'High spam score: ' . $spamScore
            ];
        }

        // Check for contact information
        $hasContactInfo = $this->containsContactInformation($fullText);
        if ($hasContactInfo) {
            return [
                'requiresManualReview' => true,
                'autoApproved' => false,
                'reason' => 'Contains contact information'
            ];
        }

        // Check message length (too short might be spam)
        if (strlen($content) < 20) {
            return [
                'requiresManualReview' => true,
                'autoApproved' => false,
                'reason' => 'Message too short'
            ];
        }

        // Clean message - auto approve
        return [
            'requiresManualReview' => false,
            'autoApproved' => true,
            'reason' => 'Clean message'
        ];
    }

    private function calculateSpamScore(string $text): float
    {
        $score = 0.0;
        $lowerText = strtolower($text);

        // Check spam keywords
        foreach (self::SPAM_KEYWORDS as $keyword) {
            if (stripos($lowerText, $keyword) !== false) {
                $score += 0.3;
            }
        }

        // Check for excessive capitals
        $capsRatio = strlen(preg_replace('/[^A-Z]/', '', $text)) / strlen($text);
        if ($capsRatio > 0.5) {
            $score += 0.3;
        }

        // Check for excessive punctuation
        $punctuationCount = substr_count($text, '!') + substr_count($text, '?');
        if ($punctuationCount > 5) {
            $score += 0.2;
        }

        return min(1.0, $score);
    }

    private function containsContactInformation(string $text): bool
    {
        foreach (self::MODERATION_PATTERNS as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }
        return false;
    }

    public function moderateMessage(Message $message, User $moderator, bool $approve, ?string $moderatedContent = null): void
    {
        if ($approve) {
            $message->approve($moderator, $moderatedContent);

            // Send the message to recipient
            $this->sendMessageToRecipient($message);

            // Notify sender of approval
            $this->notificationService->createNotification(
                $message->getSender(),
                Notification::TYPE_MESSAGE_MODERATED,
                'Message Approved',
                'Your message has been approved and sent to the recipient.',
                ['messageId' => $message->getId()->toRfc4122()]
            );
        } else {
            $message->reject($moderator);

            // Notify sender of rejection
            $this->notificationService->createNotification(
                $message->getSender(),
                Notification::TYPE_MESSAGE_MODERATED,
                'Message Rejected',
                'Your message was rejected by our moderation team.',
                ['messageId' => $message->getId()->toRfc4122()]
            );
        }

        $this->entityManager->flush();
    }

    private function approveMessage(Message $message): void
    {
        $message->setStatus(Message::STATUS_APPROVED);
        $this->entityManager->flush();

        $this->sendMessageToRecipient($message);
    }

    private function sendMessageToRecipient(Message $message): void
    {
        // Create notification for recipient
        $this->notificationService->createMessageReceivedNotification(
            $message->getRecipient(),
            $message
        );

        // Send email notification
        $this->emailService->sendEmail(
            $message->getRecipient()->getEmail(),
            'New message: ' . $message->getSubject(),
            'emails/new_message.html.twig',
            [
                'message' => $message,
                'recipient' => $message->getRecipient()
            ]
        );
    }

    private function notifyAdminsOfPendingMessage(Message $message): void
    {
        // Get all admin users
        $admins = $this->entityManager->getRepository(User::class)->findByRole('ROLE_ADMIN');

        foreach ($admins as $admin) {
            // Create notification
            $notification = new Notification();
            $notification->setUser($admin);
            $notification->setType(Notification::TYPE_MESSAGE_MODERATED);
            $notification->setTitle('New Message Pending Moderation');
            $notification->setContent(sprintf(
                'A new message from %s requires moderation.',
                $message->getSender()->getFullName() ?? $message->getSender()->getEmail()
            ));
            $notification->setData([
                'messageId' => $message->getId()->toRfc4122(),
                'adminAction' => true
            ]);

            $this->entityManager->persist($notification);
        }

        $this->entityManager->flush();

        // Send email to primary admin
        if (!empty($admins)) {
            $this->emailService->sendEmail(
                $admins[0]->getEmail(),
                'Message Pending Moderation',
                'emails/admin/message_moderation.html.twig',
                [
                    'message' => $message,
                    'moderationUrl' => '/admin/messages/' . $message->getId() . '/moderate'
                ]
            );
        }
    }

    private function getSystemUser(): User
    {
        // Get or create system user for automatic actions
        $systemUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'system@real-estate.app']);

        if (!$systemUser) {
            $systemUser = new User();
            $systemUser->setEmail('system@real-estate.app');
            $systemUser->setPassword('no-login');
            $systemUser->setRoles(['ROLE_SYSTEM']);
            $systemUser->setIsVerified(true);

            $this->entityManager->persist($systemUser);
            $this->entityManager->flush();
        }

        return $systemUser;
    }

    public function sanitizeContent(string $content): string
    {
        // Remove contact information
        foreach (self::MODERATION_PATTERNS as $pattern) {
            $content = preg_replace($pattern, '[REMOVED]', $content);
        }

        return $content;
    }

    public function getPendingMessagesCount(): int
    {
        return $this->entityManager->getRepository(Message::class)
            ->count(['status' => Message::STATUS_PENDING]);
    }
}
