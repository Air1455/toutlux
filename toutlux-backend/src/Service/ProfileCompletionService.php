<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Message;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class ProfileCompletionService
{
    private $entityManager;
    private $mailer;
    private $twig;

    public function __construct(EntityManagerInterface $entityManager, MailerInterface $mailer, Environment $twig)
    {
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    public function calculateTrustScore(User $user): int
    {
        $score = 0;

        if ($user->isEmailVerified()) $score += 25;
        if ($user->isPhoneVerified()) $score += 25;
        if ($user->isIdentityVerified()) $score += 25;
        if ($user->isFinancialDocsVerified()) $score += 25;

        // If you want to persist the score, you would do it here:
        // $user->setTrustScore($score); // Assuming a trustScore property exists on User
        // $this->entityManager->flush();

        return $score;
    }

    public function isProfileComplete(User $user): bool
    {
        $requiredFields = [
            $user->getFirstName(),
            $user->getLastName(),
            $user->getPhoneNumber(),
            $user->getPhoneNumberIndicatif(),
            $user->getProfilePicture(),
            $user->getIdentityCardType(),
            $user->getIdentityCard(),
            $user->getSelfieWithId(),
        ];

        foreach ($requiredFields as $field) {
            if (empty($field) || $field === 'yes') {
                return false;
            }
        }

        if (!$user->isEmailVerified() || !$user->isPhoneVerified()) {
            return false;
        }

        if (!$user->hasFinancialDocuments()) {
            return false;
        }

        if (!$user->isTermsAccepted() || !$user->isPrivacyAccepted()) {
            return false;
        }

        return true;
    }

    public function getCompletionPercentage(User $user): int
    {
        $completedSteps = 0;
        $totalSteps = 5;

        $personalInfoComplete = $user->getFirstName() &&
            $user->getLastName() &&
            $user->getPhoneNumber() &&
            $user->getPhoneNumberIndicatif() &&
            $user->getProfilePicture() &&
            $user->getProfilePicture() !== 'yes' &&
            $user->isEmailVerified();

        if ($personalInfoComplete) {
            $completedSteps++;
        }

        if ($user->hasIdentityDocuments()) {
            $completedSteps++;
        }

        if ($user->hasFinancialDocuments()) {
            $completedSteps++;
        }

        if ($user->isTermsAccepted() && $user->isPrivacyAccepted()) {
            $completedSteps++;
        }

        if ($user->isEmailVerified() && $user->isPhoneVerified()) {
            $completedSteps++;
        }

        return round(($completedSteps / $totalSteps) * 100);
    }

    public function getMissingFields(User $user): array
    {
        $missing = [];

        if (!$user->getFirstName()) $missing[] = 'firstName';
        if (!$user->getLastName()) $missing[] = 'lastName';
        if (!$user->getPhoneNumber()) $missing[] = 'phoneNumber';
        if (!$user->getPhoneNumberIndicatif()) $missing[] = 'phoneNumberIndicatif';
        if (!$user->getProfilePicture() || $user->getProfilePicture() === 'yes') $missing[] = 'profilePicture';

        if (!$user->getIdentityCardType()) $missing[] = 'identityCardType';
        if (!$user->getIdentityCard()) $missing[] = 'identityCard';
        if (!$user->getSelfieWithId()) $missing[] = 'selfieWithId';

        if (!$user->hasFinancialDocuments()) {
            $missing[] = 'financialDocs';
        }

        if (!$user->isEmailVerified()) $missing[] = 'emailVerification';
        if (!$user->isPhoneVerified()) $missing[] = 'phoneVerification';

        if (!$user->isTermsAccepted()) $missing[] = 'termsAccepted';
        if (!$user->isPrivacyAccepted()) $missing[] = 'privacyAccepted';

        return $missing;
    }

    public function sendInAppMessage(User $user, string $subject, string $content, string $type = 'system'): void
    {
        $message = new Message();
        $message->setUser($user);
        $message->setSubject($subject);
        $message->setContent($content);
        $message->setType($type);
        $message->setStatus('unread');
        $message->setIsRead(false);
        $message->setEmailSent(false); // This message is in-app, not necessarily email

        $this->entityManager->persist($message);
        $this->entityManager->flush();
    }

    public function notifyUserOfValidationStatus(User $user, string $documentType, bool $isValidated, ?string $reason = null): void
    {
        $subject = '';
        $content = '';
        $template = '';

        if ($isValidated) {
            $subject = sprintf('Vos %s ont été validés !', $documentType);
            $content = sprintf('Nous avons le plaisir de vous informer que vos %s ont été vérifiés et validés par notre équipe.', $documentType);
            $template = sprintf('emails/user_%s_validated.html.twig', str_replace(' ', '_', $documentType));
        } else {
            $subject = sprintf('Vos %s ont été refusés', $documentType);
            $content = sprintf('Nous avons examiné vos %s, mais nous n\'avons pas pu les valider. Raison : %s', $documentType, $reason ?? 'Non spécifié');
            $template = sprintf('emails/user_%s_rejected.html.twig', str_replace(' ', '_', $documentType));
        }

        // Send email
        $email = (new Email())
            ->from('no-reply@toutlux.com')
            ->to($user->getEmail())
            ->subject($subject)
            ->html($this->twig->render($template, ['user' => $user, 'reason' => $reason]));
        $this->mailer->send($email);

        // Send in-app message
        $this->sendInAppMessage($user, $subject, $content, 'system');
    }
}