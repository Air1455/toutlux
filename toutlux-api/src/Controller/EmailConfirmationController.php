<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\Messaging\EmailService;
use App\Service\User\UserWorkflowService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Psr\Log\LoggerInterface;

class EmailConfirmationController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private EmailService $emailService,
        private UserWorkflowService $userWorkflowService,
        private LoggerInterface $logger
    ) {}

    #[Route('/api/email/confirm/{token}', name: 'api_email_confirm', methods: ['GET'])]
    public function confirmEmail(string $token): JsonResponse
    {
        try {
            $user = $this->userRepository->findOneBy(['emailConfirmationToken' => $token]);

            if (!$user) {
                return new JsonResponse([
                    'error' => 'Invalid token',
                    'message' => 'The confirmation link is invalid.'
                ], 400);
            }

            if ($user->isEmailConfirmationTokenExpired()) {
                return new JsonResponse([
                    'error' => 'Token expired',
                    'message' => 'The confirmation link has expired. Please request a new one.'
                ], 400);
            }

            if ($user->isEmailVerified()) {
                return new JsonResponse([
                    'message' => 'Email already confirmed'
                ], 200);
            }

            // Confirmer l'email
            $user->setIsEmailVerified(true);
            $user->setEmailVerifiedAt(new \DateTimeImmutable());
            $user->setEmailConfirmationToken(null);
            $user->setEmailConfirmationTokenExpiresAt(null);
            $user->setEmailVerificationAttempts(0);

            $this->entityManager->flush();

            // Déclencher le workflow de confirmation
            $this->userWorkflowService->handleEmailConfirmation($user);

            $this->logger->info('Email confirmed successfully', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail()
            ]);

            return new JsonResponse([
                'message' => 'Email confirmed successfully',
                'redirect_url' => '/login?email_confirmed=true'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Email confirmation error: ' . $e->getMessage());

            return new JsonResponse([
                'error' => 'Confirmation failed',
                'message' => 'An error occurred during email confirmation'
            ], 500);
        }
    }

    #[Route('/api/email/resend-confirmation', name: 'api_email_resend_confirmation', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function resendConfirmation(): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            if (!$user) {
                return new JsonResponse(['error' => 'User not found'], 404);
            }

            if ($user->isEmailVerified()) {
                return new JsonResponse([
                    'message' => 'Email already confirmed'
                ], 200);
            }

            if (!$user->isEmailConfirmationRequestAllowed()) {
                $nextAllowedAt = $user->getNextEmailConfirmationAllowedAt();
                return new JsonResponse([
                    'error' => 'Too many attempts',
                    'message' => 'Please wait before requesting a new confirmation email',
                    'next_allowed_at' => $nextAllowedAt?->format('c')
                ], 429);
            }

            // Mettre à jour les tentatives
            $attempts = $user->getEmailVerificationAttempts() ?? 0;
            $user->setEmailVerificationAttempts($attempts + 1);
            $user->setLastEmailVerificationRequestAt(new \DateTimeImmutable());

            // Envoyer nouvel email
            $emailSent = $this->emailService->sendEmailConfirmation($user);

            $this->entityManager->flush();

            if ($emailSent) {
                $this->logger->info('Confirmation email resent', [
                    'user_id' => $user->getId(),
                    'attempt' => $attempts + 1
                ]);

                return new JsonResponse([
                    'message' => 'Confirmation email sent successfully',
                    'attempts_used' => $attempts + 1,
                    'attempts_remaining' => max(0, 3 - ($attempts + 1))
                ]);
            } else {
                return new JsonResponse([
                    'error' => 'Failed to send email',
                    'message' => 'Could not send confirmation email. Please try again later.'
                ], 500);
            }

        } catch (\Exception $e) {
            $this->logger->error('Resend confirmation error: ' . $e->getMessage());

            return new JsonResponse([
                'error' => 'Request failed',
                'message' => 'An error occurred while sending confirmation email'
            ], 500);
        }
    }
}
