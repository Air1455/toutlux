<?php

namespace App\Service\Email;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Psr\Log\LoggerInterface;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private string $fromEmail = 'noreply@realestate.app',
        private string $fromName = 'Real Estate App'
    ) {}

    public function sendEmail(
        string $to,
        string $subject,
        string $template,
        array $context = [],
        ?string $replyTo = null
    ): void {
        try {
            $email = (new TemplatedEmail())
                ->from($this->fromEmail)
                ->to($to)
                ->subject($subject)
                ->htmlTemplate($template)
                ->context($context);

            if ($replyTo) {
                $email->replyTo($replyTo);
            }

            $this->mailer->send($email);

            $this->logger->info('Email sent successfully', [
                'to' => $to,
                'subject' => $subject,
                'template' => $template
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

    public function sendRawEmail(
        string $to,
        string $subject,
        string $textContent,
        ?string $htmlContent = null
    ): void {
        try {
            $email = (new Email())
                ->from($this->fromEmail)
                ->to($to)
                ->subject($subject)
                ->text($textContent);

            if ($htmlContent) {
                $email->html($htmlContent);
            }

            $this->mailer->send($email);

            $this->logger->info('Raw email sent successfully', [
                'to' => $to,
                'subject' => $subject
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send raw email', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function sendBulkEmail(
        array $recipients,
        string $subject,
        string $template,
        array $context = []
    ): void {
        foreach ($recipients as $recipient) {
            try {
                $this->sendEmail($recipient, $subject, $template, $context);
            } catch (\Exception $e) {
                $this->logger->error('Failed to send bulk email to recipient', [
                    'recipient' => $recipient,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
