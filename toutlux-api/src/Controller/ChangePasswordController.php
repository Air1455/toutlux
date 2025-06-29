<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\Messaging\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Psr\Log\LoggerInterface;

#[IsGranted('ROLE_USER')]
class ChangePasswordController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
        private LoggerInterface $logger,
        private EmailService $emailService
    ) {
    }

    #[Route('/api/profile/change-password', name: 'api_change_password', methods: ['POST', 'PATCH'])]
    public function changePassword(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            if (!$user) {
                return new JsonResponse(['error' => 'User not found'], 404);
            }

            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse(['error' => 'Invalid JSON data'], 400);
            }

            $constraints = new Assert\Collection([
                'currentPassword' => [
                    new Assert\NotBlank(['message' => 'Current password is required']),
                    new Assert\Type(['type' => 'string'])
                ],
                'newPassword' => [
                    new Assert\NotBlank(['message' => 'New password is required']),
                    new Assert\Length([
                        'min' => 8,
                        'minMessage' => 'Password must be at least 8 characters long'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
                        'message' => 'Password must contain at least one lowercase letter, one uppercase letter, and one number'
                    ])
                ]
            ]);

            $violations = $this->validator->validate($data, $constraints);

            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $errors[$violation->getPropertyPath()] = $violation->getMessage();
                }
                return new JsonResponse([
                    'error' => 'Validation failed',
                    'details' => $errors
                ], 400);
            }

            $currentPassword = $data['currentPassword'];
            $newPassword = $data['newPassword'];

            if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->logger->warning('Invalid current password attempt', [
                    'user_id' => $user->getId(),
                    'ip' => $request->getClientIp()
                ]);

                return new JsonResponse([
                    'error' => 'Current password is incorrect'
                ], 400);
            }

            if ($this->passwordHasher->isPasswordValid($user, $newPassword)) {
                return new JsonResponse([
                    'error' => 'New password must be different from current password'
                ], 400);
            }

            $hashedNewPassword = $this->passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedNewPassword);
            $user->setUpdatedAt(new \DateTimeImmutable());

            $this->entityManager->flush();

            $this->logger->info('Password changed successfully', [
                'user_id' => $user->getId(),
                'ip' => $request->getClientIp()
            ]);

            // Envoi de l'email de notification
            $this->emailService->sendPasswordChangedNotification($user);

            return new JsonResponse([
                'success' => true,
                'message' => 'Password changed successfully'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Password change error: ' . $e->getMessage(), [
                'user_id' => $this->getUser()?->getId(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'error' => 'Password change failed',
                'message' => $_ENV['APP_ENV'] === 'dev' ? $e->getMessage() : 'An error occurred while changing password'
            ], 500);
        }
    }

    #[Route('/api/profile/password-strength', name: 'api_password_strength', methods: ['POST'])]
    public function checkPasswordStrength(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $password = $data['password'] ?? '';

            if (empty($password)) {
                return new JsonResponse(['strength' => 'weak', 'score' => 0]);
            }

            $score = 0;
            $feedback = [];

            if (strlen($password) >= 8) {
                $score += 20;
            } else {
                $feedback[] = 'At least 8 characters required';
            }

            if (preg_match('/[a-z]/', $password)) {
                $score += 20;
            } else {
                $feedback[] = 'Include lowercase letters';
            }

            if (preg_match('/[A-Z]/', $password)) {
                $score += 20;
            } else {
                $feedback[] = 'Include uppercase letters';
            }

            if (preg_match('/\d/', $password)) {
                $score += 20;
            } else {
                $feedback[] = 'Include numbers';
            }

            if (preg_match('/[^a-zA-Z\d]/', $password)) {
                $score += 20;
            } else {
                $feedback[] = 'Include special characters';
            }

            $strength = 'weak';
            if ($score >= 80) {
                $strength = 'strong';
            } elseif ($score >= 60) {
                $strength = 'medium';
            }

            return new JsonResponse([
                'strength' => $strength,
                'score' => $score,
                'feedback' => $feedback
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to check password strength'], 500);
        }
    }
}
