<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\EmailNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;

#[IsGranted('ROLE_USER')]
class ProfileStepController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private LoggerInterface $logger,
//        private EmailNotificationService $emailService // ✅ AJOUT
    ) {
    }

    #[Route('/api/profile/step/{step}', name: 'api_profile_step', methods: ['PATCH'])]
    public function updateStep(int $step, Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            if (!$user) {
                return new JsonResponse(['error' => 'User not found'], 404);
            }

            if ($step < 0 || $step > 3) {
                return new JsonResponse(['error' => 'Invalid step number. Must be between 0 and 3'], 400);
            }

            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse(['error' => 'Invalid JSON data'], 400);
            }

            $this->logger->info("Step {$step} data received", [
                'user_id' => $user->getId(),
                'data' => $data
            ]);

            // ✅ AJOUT: Détecter les documents avant mise à jour
            $hadIdentityDocs = $user->getIdentityCard() && $user->getSelfieWithId();
            $hadFinancialDocs = $user->getIncomeProof() || $user->getOwnershipProof();

            $updatedFields = $this->updateStepData($user, $step, $data);

            // Validation supplémentaire pour l'étape 2
            if ($step === 2 && !$data['incomeProof'] && !$data['ownershipProof']) {
                return new JsonResponse([
                    'error' => 'Validation failed',
                    'details' => ['financialDocs' => 'At least one financial document is required']
                ], 400);
            }

            $errors = $this->validator->validate($user);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[$error->getPropertyPath()] = $error->getMessage();
                }
                return new JsonResponse([
                    'error' => 'Validation failed',
                    'details' => $errorMessages
                ], 400);
            }

            $user->setUpdatedAt(new \DateTimeImmutable());
            $this->entityManager->flush();

            // ✅ AJOUT: Notifications email pour nouveaux documents
            $notifications = [];

            // Étape 1: Documents d'identité
            if ($step === 1) {
                $hasIdentityDocsNow = $user->getIdentityCard() && $user->getSelfieWithId();
//                if ($hasIdentityDocsNow && !$hadIdentityDocs) {
//                    $emailSent = $this->emailService->sendIdentityDocumentsForReview($user);
//                    $notifications['identity_docs_sent'] = $emailSent;
//
//                    $this->logger->info("Identity documents submitted for review", [
//                        'user_id' => $user->getId(),
//                        'email_sent' => $emailSent
//                    ]);
//                }
            }

            // Étape 2: Documents financiers
            if ($step === 2) {
                $hasFinancialDocsNow = $user->getIncomeProof() || $user->getOwnershipProof();
//                if ($hasFinancialDocsNow && !$hadFinancialDocs) {
//                    $emailSent = $this->emailService->sendFinancialDocumentsForReview($user);
//                    $notifications['financial_docs_sent'] = $emailSent;
//
//                    $this->logger->info("Financial documents submitted for review", [
//                        'user_id' => $user->getId(),
//                        'email_sent' => $emailSent
//                    ]);
//                }
            }

            $this->logger->info("Profile step {$step} updated successfully", [
                'user_id' => $user->getId(),
                'updated_fields' => $updatedFields,
                'notifications' => $notifications
            ]);

            $response = [
                'success' => true,
                'step' => $step,
                'updated_fields' => $updatedFields,
                'user' => [
                    'id' => $user->getId(),
                    'completion_percentage' => $user->getCompletionPercentage(),
                    'is_profile_complete' => $user->isProfileComplete(),
                    'missing_fields' => $user->getMissingFields(),
                ],
            ];

            // ✅ AJOUT: Inclure les notifications dans la réponse
            if (!empty($notifications)) {
                $response['notifications'] = $notifications;

                if (isset($notifications['identity_docs_sent']) && $notifications['identity_docs_sent']) {
                    $response['message'] = 'Documents d\'identité soumis pour validation. Vous recevrez un email de confirmation.';
                } elseif (isset($notifications['financial_docs_sent']) && $notifications['financial_docs_sent']) {
                    $response['message'] = 'Documents financiers soumis pour validation. Vous recevrez un email de confirmation.';
                }
            }

            return new JsonResponse($response);

        } catch (\Exception $e) {
            $this->logger->error("Profile step update error: " . $e->getMessage(), [
                'step' => $step,
                'user_id' => $this->getUser()?->getId(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'error' => 'Update failed',
                'message' => $_ENV['APP_ENV'] === 'dev' ? $e->getMessage() : 'An error occurred during profile update'
            ], 500);
        }
    }

    private function updateStepData(User $user, int $step, array $data): array
    {
        $updatedFields = [];

        $stepFieldsMap = [
            0 => ['firstName', 'lastName', 'phoneNumber', 'phoneNumberIndicatif', 'profilePicture'],
            1 => ['identityCardType', 'identityCard', 'selfieWithId'],
            2 => ['incomeSource', 'occupation', 'incomeProof', 'ownershipProof'],
            3 => ['termsAccepted', 'privacyAccepted', 'marketingAccepted'],
        ];

        $allowedFields = $stepFieldsMap[$step] ?? [];

        foreach ($allowedFields as $field) {
            if (!isset($data[$field])) {
                $this->logger->info("Field not found in data", [
                    'step' => $step,
                    'missing_field' => $field,
                    'data_keys' => array_keys($data)
                ]);
                continue;
            }

            $setter = 'set' . ucfirst($field);
            $getter = 'get' . ucfirst($field);

            if (in_array($field, ['termsAccepted', 'privacyAccepted', 'marketingAccepted'])) {
                $getter = 'is' . ucfirst($field);
            }

            if (method_exists($user, $setter) && method_exists($user, $getter)) {
                $currentValue = $user->$getter();
                $newValue = $data[$field];

                if (is_string($newValue) && !in_array($field, ['profilePicture', 'identityCard', 'selfieWithId', 'incomeProof', 'ownershipProof'])) {
                    $newValue = trim($newValue) ?: null;
                }

                $this->logger->info("Processing field", [
                    'field' => $field,
                    'current_value' => $currentValue,
                    'new_value' => $newValue
                ]);

                if ($currentValue !== $newValue) {
                    $user->$setter($newValue);
                    $updatedFields[] = $field;

                    $this->logger->info("Field updated", [
                        'field' => $field,
                        'old_value' => $currentValue,
                        'new_value' => $newValue
                    ]);
                }
            } else {
                $this->logger->error("Methods not found for field", [
                    'field' => $field,
                    'setter_exists' => method_exists($user, $setter),
                    'getter_exists' => method_exists($user, $getter),
                    'setter' => $setter,
                    'getter' => $getter
                ]);
            }
        }

        return $updatedFields;
    }

    #[Route('/api/profile/completion', name: 'api_profile_completion', methods: ['GET'])]
    public function getCompletion(): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            if (!$user) {
                return new JsonResponse(['error' => 'User not found'], 404);
            }

            return new JsonResponse([
                'completion_percentage' => $user->getCompletionPercentage(),
                'is_profile_complete' => $user->isProfileComplete(),
                'missing_fields' => $user->getMissingFields(),
                'user_summary' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'full_name' => $user->getFullName(),
                    'phone_number' => $user->getPhoneNumber(),
                    'phone_number_indicatif' => $user->getPhoneNumberIndicatif(),
                    'verification_status' => [
                        'email_verified' => $user->isEmailVerified(),
                        'phone_verified' => $user->isPhoneVerified(),
                        'identity_verified' => $user->isIdentityVerified(),
                    ],
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error("Profile completion error: " . $e->getMessage());

            return new JsonResponse([
                'error' => 'Failed to get completion status'
            ], 500);
        }
    }

    #[Route('/api/profile/debug', name: 'api_profile_debug', methods: ['GET'])]
    public function debugCompletion(): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            if (!$user) {
                return new JsonResponse(['error' => 'User not found'], 404);
            }

            $debug = [
                'user_id' => $user->getId(),
                'raw_data' => [
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'phoneNumber' => $user->getPhoneNumber(),
                    'phoneNumberIndicatif' => $user->getPhoneNumberIndicatif(),
                    'profilePicture' => $user->getProfilePicture(),
                    'identityCardType' => $user->getIdentityCardType(),
                    'identityCard' => $user->getIdentityCard(),
                    'selfieWithId' => $user->getSelfieWithId(),
                    'incomeProof' => $user->getIncomeProof(),
                    'ownershipProof' => $user->getOwnershipProof(),
                    'isEmailVerified' => $user->isEmailVerified(),
                    'isPhoneVerified' => $user->isPhoneVerified(),
                    'termsAccepted' => $user->isTermsAccepted(),
                    'privacyAccepted' => $user->isPrivacyAccepted(),
                ],
                'completion_debug' => $user->getCompletionDebug(),
            ];

            return new JsonResponse($debug);

        } catch (\Exception $e) {
            $this->logger->error("Debug completion error: " . $e->getMessage());
            return new JsonResponse(['error' => 'Debug failed'], 500);
        }
    }
}
