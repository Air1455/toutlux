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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class EmailSenderService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EmailLogRepository $emailLogRepository,
        private MailerInterface $mailer,
        private Environment $twig,
        private UrlGeneratorInterface $urlGenerator,
        private string $fromEmail = 'noreply@toutlux.com',
        private string $appName = 'ToutLux'
    ) {}

    public function sendWelcomeEmail(User $user): bool
    {
        return $this->sendTemplatedEmail(
            $user->getEmail(),
            'Bienvenue sur ' . $this->appName,
            EmailTemplate::WELCOME,
            ['user' => $user],
            $user
        );
    }

    public function sendEmailConfirmation(User $user): bool
    {
        // Générer token si pas déjà fait
        if (!$user->getEmailConfirmationToken()) {
            $token = bin2hex(random_bytes(32));
            $user->setEmailConfirmationToken($token);
            $user->setEmailConfirmationTokenExpiresAt(new \DateTimeImmutable('+24 hours'));
            $this->entityManager->flush();
        }

        $confirmationUrl = $this->urlGenerator->generate(
            'api_email_confirm',
            ['token' => $user->getEmailConfirmationToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return $this->sendTemplatedEmail(
            $user->getEmail(),
            'Confirmez votre email - ' . $this->appName,
            'email_confirmation',
            [
                'user' => $user,
                'confirmationUrl' => $confirmationUrl
            ],
            $user
        );
    }

    public function sendEmailConfirmedNotification(User $user): bool
    {
        return $this->sendTemplatedEmail(
            $user->getEmail(),
            'Email confirmé avec succès',
            EmailTemplate::EMAIL_CONFIRMED,
            ['user' => $user],
            $user
        );
    }

    public function sendIdentityDocumentsForReview(User $user): bool
    {
        return $this->sendTemplatedEmail(
            $this->fromEmail,
            'Nouveaux documents d\'identité à vérifier - ' . $user->getDisplayName(),
            'admin_identity_docs',
            ['user' => $user],
            $user
        );
    }

    public function sendFinancialDocumentsForReview(User $user): bool
    {
        return $this->sendTemplatedEmail(
            $this->fromEmail,
            'Nouveaux documents financiers à vérifier - ' . $user->getDisplayName(),
            'admin_financial_docs',
            ['user' => $user],
            $user
        );
    }

    public function sendDocumentsApprovedNotification(User $user): bool
    {
        return $this->sendTemplatedEmail(
            $user->getEmail(),
            'Documents approuvés',
            EmailTemplate::DOCUMENTS_APPROVED,
            ['user' => $user],
            $user
        );
    }

    public function sendNewMessageNotification(Message $message): bool
    {
        return $this->sendTemplatedEmail(
            $this->fromEmail,
            'Nouveau message de ' . $message->getUser()->getDisplayName(),
            EmailTemplate::NEW_MESSAGE,
            ['message' => $message],
            $message->getUser(),
            $message
        );
    }

    public function sendAdminReply(Message $message, string $replyContent): bool
    {
        return $this->sendTemplatedEmail(
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
        string $template,
        array $data = [],
        ?User $user = null,
        ?Message $message = null
    ): bool {
        // Créer le log
        $emailLog = new EmailLog();
        $emailLog->setToEmail($toEmail)
            ->setSubject($subject)
            ->setTemplate($template)
            ->setTemplateData($data)
            ->setUser($user)
            ->setMessage($message);

        $this->entityManager->persist($emailLog);

        try {
            // Template path
            $templatePath = $template instanceof EmailTemplate
                ? "emails/{$template->value}.html.twig"
                : "emails/{$template}.html.twig";

            // Render template
            $htmlContent = $this->twig->render(
                $templatePath,
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

            $this->entityManager->flush();
            return true;

        } catch (\Exception $e) {
            $emailLog->setStatus('failed')
                ->setErrorMessage($e->getMessage());
            $this->entityManager->flush();
            return false;
        }
    }
}