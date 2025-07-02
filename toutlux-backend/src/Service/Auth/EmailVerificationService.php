<?php

namespace App\Service\Auth;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Email\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\InvalidArgumentException;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class EmailVerificationService
{
    public function __construct(
        private VerifyEmailHelperInterface $verifyEmailHelper,
        private EmailService $emailService,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public function sendVerificationEmail(User $user): void
    {
        if ($user->isVerified()) {
            return;
        }

        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            'app_verify_email',
            $user->getId(),
            $user->getEmail(),
            ['id' => $user->getId()]
        );

        $context = [
            'user' => $user,
            'signedUrl' => $signatureComponents->getSignedUrl(),
            'expiresAtMessageKey' => $signatureComponents->getExpirationMessageKey(),
            'expiresAtMessageData' => $signatureComponents->getExpirationMessageData(),
        ];

        $this->emailService->sendEmail(
            $user->getEmail(),
            'Confirmez votre adresse email - TOUTLUX',
            'emails/email_verification.html.twig',
            $context
        );

        // Créer une notification in-app
        $this->createInAppNotification($user, 'Veuillez confirmer votre adresse email');
    }

    public function verifyUserEmail(string $uri, User $user): void
    {
        try {
            $this->verifyEmailHelper->validateEmailConfirmation($uri, $user->getId(), $user->getEmail());

            $user->setIsVerified(true);
            $user->setVerifiedAt(new \DateTimeImmutable());

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // Notification de succès
            $this->createInAppNotification($user, 'Votre email a été confirmé avec succès');

        } catch (\Exception $e) {
            throw new InvalidArgumentException('Le lien de vérification est invalide ou a expiré.');
        }
    }

    public function resendVerificationEmail(string $email): bool
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            return false;
        }

        if ($user->isVerified()) {
            throw new InvalidArgumentException('Cet email est déjà vérifié.');
        }

        // Vérifier le délai entre les envois (5 minutes)
        $lastSent = $user->getLastVerificationEmailSentAt();
        if ($lastSent && $lastSent->getTimestamp() > (time() - 300)) {
            $remainingTime = 300 - (time() - $lastSent->getTimestamp());
            throw new InvalidArgumentException(
                sprintf('Veuillez attendre %d secondes avant de renvoyer un email.', $remainingTime)
            );
        }

        $user->setLastVerificationEmailSentAt(new \DateTimeImmutable());
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->sendVerificationEmail($user);

        return true;
    }

    private function createInAppNotification(User $user, string $message): void
    {
        // Cette méthode sera implémentée avec le NotificationService
        // Pour l'instant, on la laisse vide
    }

    public function generateVerificationUrl(User $user): string
    {
        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            'app_verify_email',
            $user->getId(),
            $user->getEmail(),
            ['id' => $user->getId()]
        );

        return $signatureComponents->getSignedUrl();
    }

    public function isTokenValid(string $token, User $user): bool
    {
        try {
            $uri = $this->urlGenerator->generate('app_verify_email', [
                'id' => $user->getId(),
                'token' => $token
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $this->verifyEmailHelper->validateEmailConfirmation($uri, $user->getId(), $user->getEmail());
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
