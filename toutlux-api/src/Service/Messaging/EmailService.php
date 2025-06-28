<?php

namespace App\Service\Messaging;

use App\Entity\EmailLog;
use App\Entity\Message;
use App\Entity\User;
use App\Enum\EmailTemplate;
use App\Repository\EmailLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class EmailService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EmailLogRepository $emailLogRepository,
        private MailerInterface $mailer,
        private Environment $twig,
        private string $fromEmail = 'noreply@example.com',
        private string $appName = 'ImmobilierApp'
    ) {}

    public function sendWelcomeEmail(User $user): void
    {
        $this->sendTemplatedEmail(
            $user->getEmail(),
            'Bienvenue sur ' . $this->appName,
            EmailTemplate::WELCOME,
            ['user' => $user],
            $user
        );
    }

    public function sendEmailConfirmedNotification(User $user): void
    {
        $this->sendTemplatedEmail(
            $user->getEmail(),
            'Email confirmé avec succès',
            EmailTemplate::EMAIL_CONFIRMED,
            ['user' => $user],
            $user
        );
    }

    public function sendDocumentsApprovedNotification(User $user): void
    {
        $this->sendTemplatedEmail(
            $user->getEmail(),
            'Documents approuvés',
            EmailTemplate::DOCUMENTS_APPROVED,
            ['user' => $user],
            $user
        );
    }

    public function sendNewMessageNotification(Message $message): void
    {
        $this->sendTemplatedEmail(
            $this->fromEmail, // À l'admin
            'Nouveau message de ' . $message->getUser()->getDisplayName(),
            EmailTemplate::NEW_MESSAGE,
            ['message' => $message],
            $message->getUser(),
            $message
        );
    }

    public function sendAdminReply(Message $message, string $replyContent): void
    {
        $this->sendTemplatedEmail(
            $message->getUser()->getEmail(),
            'Réponse à votre message: ' . $message->getSubject(),
            EmailTemplate::ADMIN_REPLY,
            [
                'user' => $message->getUser(),
                'originalMessage' => $message,
                'replyContent' => $replyContent
            ],
            $message->getUser(),
            $message
        );
    }

    private function sendTemplatedEmail(
        string $toEmail,
        string $subject,
        EmailTemplate $template,
        array $data = [],
        ?User $user = null,
        ?Message $message = null
    ): void {
        // Créer le log
        $emailLog = new EmailLog();
        $emailLog->setToEmail($toEmail)
            ->setSubject($subject)
            ->setTemplate($template->value)
            ->setTemplateData($data)
            ->setUser($user)
            ->setMessage($message);

        $this->entityManager->persist($emailLog);

        try {
            // Render template
            $htmlContent = $this->twig->render(
                "emails/{$template->value}.html.twig",
                array_merge($data, ['app_name' => $this->appName])
            );

            // Créer et envoyer l'email
            $email = (new Email())
                ->from($this->fromEmail)
                ->to($toEmail)
                ->subject($subject)
                ->html($htmlContent);

            $this->mailer->send($email);

            $emailLog->setStatus('sent');

        } catch (\Exception $e) {
            $emailLog->setStatus('failed')
                ->setErrorMessage($e->getMessage());
        }

        $this->entityManager->flush();
    }

    public function processPendingEmails(): void
    {
        $pendingEmails = $this->emailLogRepository->findPendingEmails();

        foreach ($pendingEmails as $emailLog) {
            try {
                $htmlContent = $this->twig->render(
                    "emails/{$emailLog->getTemplate()}.html.twig",
                    array_merge($emailLog->getTemplateData(), ['app_name' => $this->appName])
                );

                $email = (new Email())
                    ->from($this->fromEmail)
                    ->to($emailLog->getToEmail())
                    ->subject($emailLog->getSubject())
                    ->html($htmlContent);

                $this->mailer->send($email);
                $emailLog->setStatus('sent');

            } catch (\Exception $e) {
                $emailLog->setStatus('failed')
                    ->setErrorMessage($e->getMessage());
            }
        }

        $this->entityManager->flush();
    }
}
