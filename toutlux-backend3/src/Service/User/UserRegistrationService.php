<?php

namespace App\Service\User;

use App\Entity\User;
use App\Entity\UserProfile;
use App\Entity\Notification;
use App\Service\Notification\NotificationService;
use App\Service\Email\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UserRegistrationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private EmailService $emailService,
        private NotificationService $notificationService,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public function registerUser(string $email, string $plainPassword, ?string $googleId = null): User
    {
        $user = new User();
        $user->setEmail($email);

        if ($googleId) {
            $user->setGoogleId($googleId);
            $user->setIsVerified(true); // Google users are pre-verified
        } else {
            // Hash password for regular registration
            $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
        }

        // Create empty profile
        $profile = new UserProfile();
        $user->setProfile($profile);

        // Persist user
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Send welcome email
        $this->sendWelcomeEmail($user);

        // Create welcome notification
        $this->notificationService->createWelcomeNotification($user);

        // Send verification email if not Google user
        if (!$googleId) {
            $this->sendVerificationEmail($user);
        }

        return $user;
    }

    public function registerGoogleUser(array $googleUserData): User
    {
        $email = $googleUserData['email'];
        $googleId = $googleUserData['id'];

        // Check if user already exists
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if ($existingUser) {
            // Link Google account to existing user
            $existingUser->setGoogleId($googleId);
            $existingUser->setIsVerified(true);
            $this->entityManager->flush();

            return $existingUser;
        }

        // Create new user with Google data
        $user = $this->registerUser($email, Uuid::v4()->toRfc4122(), $googleId);

        // Pre-fill profile if data available
        if (isset($googleUserData['given_name']) || isset($googleUserData['family_name'])) {
            $profile = $user->getProfile();
            $profile->setFirstName($googleUserData['given_name'] ?? '');
            $profile->setLastName($googleUserData['family_name'] ?? '');
            $this->entityManager->flush();
        }

        return $user;
    }

    public function verifyEmail(User $user): void
    {
        $user->setIsVerified(true);
        $this->entityManager->flush();

        // Create notification
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setType(Notification::TYPE_EMAIL_VERIFICATION);
        $notification->setTitle('Email Verified');
        $notification->setContent('Your email has been successfully verified. You can now access all features.');

        $this->entityManager->persist($notification);
        $this->entityManager->flush();
    }

    private function sendWelcomeEmail(User $user): void
    {
        $this->emailService->sendEmail(
            $user->getEmail(),
            'Welcome to Real Estate App',
            'emails/welcome.html.twig',
            [
                'user' => $user,
                'isGoogleUser' => $user->getGoogleId() !== null
            ]
        );
    }

    private function sendVerificationEmail(User $user): void
    {
        $verificationUrl = $this->generateVerificationUrl($user);

        $this->emailService->sendEmail(
            $user->getEmail(),
            'Verify your email address',
            'emails/verify.html.twig',
            [
                'user' => $user,
                'verificationUrl' => $verificationUrl
            ]
        );

        // Create in-app notification
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setType(Notification::TYPE_EMAIL_VERIFICATION);
        $notification->setTitle('Verify your email');
        $notification->setContent('Please check your email to verify your account.');

        $this->entityManager->persist($notification);
        $this->entityManager->flush();
    }

    private function generateVerificationUrl(User $user): string
    {
        // Generate a secure token
        $token = base64_encode(hash('sha256', $user->getId() . $user->getEmail() . $user->getCreatedAt()->getTimestamp(), true));

        return $this->urlGenerator->generate('app_verify_email', [
            'id' => $user->getId(),
            'token' => $token
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    public function isVerificationTokenValid(User $user, string $token): bool
    {
        $expectedToken = base64_encode(hash('sha256', $user->getId() . $user->getEmail() . $user->getCreatedAt()->getTimestamp(), true));

        return hash_equals($expectedToken, $token);
    }
}
