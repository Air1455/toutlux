<?php

namespace App\Service\Email;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Psr\Log\LoggerInterface;

class EmailService
{
    private const FROM_EMAIL = 'no-reply@toutlux.com';
    private const FROM_NAME = 'TOUTLUX';

    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private string $environment
    ) {}

    public function sendEmail(
        string $to,
        string $subject,
        string $template,
        array $context = [],
        ?string $replyTo = null,
        array $attachments = []
    ): void {
        try {
            $email = (new TemplatedEmail())
                ->from(new Address(self::FROM_EMAIL, self::FROM_NAME))
                ->to($to)
                ->subject($subject)
                ->htmlTemplate($template)
                ->context($context);

            if ($replyTo) {
                $email->replyTo($replyTo);
            }

            foreach ($attachments as $attachment) {
                if (isset($attachment['path']) && file_exists($attachment['path'])) {
                    $email->attachFromPath(
                        $attachment['path'],
                        $attachment['name'] ?? basename($attachment['path']),
                        $attachment['contentType'] ?? null
                    );
                }
            }

            // En développement, logger l'email au lieu de l'envoyer
            if ($this->environment === 'dev') {
                $this->logger->info('Email would be sent', [
                    'to' => $to,
                    'subject' => $subject,
                    'template' => $template,
                    'context' => $context
                ]);
            }

            $this->mailer->send($email);

            $this->logger->info('Email sent successfully', [
                'to' => $to,
                'subject' => $subject
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to send email', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function sendBulkEmails(array $recipients, string $subject, string $template, array $context = []): array
    {
        $results = [
            'success' => [],
            'failed' => []
        ];

        foreach ($recipients as $recipient) {
            try {
                $recipientEmail = is_array($recipient) ? $recipient['email'] : $recipient;
                $recipientContext = is_array($recipient) && isset($recipient['context'])
                    ? array_merge($context, $recipient['context'])
                    : $context;

                $this->sendEmail($recipientEmail, $subject, $template, $recipientContext);
                $results['success'][] = $recipientEmail;

            } catch (\Exception $e) {
                $results['failed'][] = [
                    'email' => $recipientEmail,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    public function sendAdminNotification(string $subject, string $template, array $context = []): void
    {
        $adminEmails = $this->getAdminEmails();

        foreach ($adminEmails as $adminEmail) {
            try {
                $this->sendEmail($adminEmail, '[ADMIN] ' . $subject, $template, $context);
            } catch (\Exception $e) {
                $this->logger->error('Failed to send admin notification', [
                    'email' => $adminEmail,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    private function getAdminEmails(): array
    {
        // TODO: Récupérer depuis la base de données
        return [
            'admin@toutlux.com',
            'support@toutlux.com'
        ];
    }

    public function createEmailContext(array $baseContext = []): array
    {
        return array_merge([
            'app_name' => 'TOUTLUX',
            'app_url' => $_ENV['APP_URL'] ?? 'https://toutlux.com',
            'support_email' => 'support@toutlux.com',
            'current_year' => date('Y'),
        ], $baseContext);
    }

    public function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
