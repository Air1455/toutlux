<?php

namespace App\Controller;

use App\Entity\User;
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
        private LoggerInterface $logger
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

            // Validation de l'étape
            if ($step < 0 || $step > 3) {
                return new JsonResponse(['error' => 'Invalid step number. Must be between 0 and 3'], 400);
            }

            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse(['error' => 'Invalid JSON data'], 400);
            }

            // Log des données reçues pour debug
            $this->logger->info("Step {$step} data received", [
                'user_id' => $user->getId(),
                'data' => $data
            ]);

            // Mise à jour selon l'étape
            $updatedFields = $this->updateStepData($user, $step, $data);

            // Validation supplémentaire pour l'étape 2 (documents financiers)
            if ($step === 2 && !$data['incomeProof'] && !$data['ownershipProof']) {
                return new JsonResponse([
                    'error' => 'Validation failed',
                    'details' => ['financialDocs' => 'At least one financial document is required']
                ], 400);
            }

            // Validation des données modifiées
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

            // Mise à jour du timestamp
            $user->setUpdatedAt(new \DateTimeImmutable());

            // Sauvegarde
            $this->entityManager->flush();

            // Log du résultat pour debug
            $this->logger->info("Profile step {$step} updated successfully", [
                'user_id' => $user->getId(),
                'updated_fields' => $updatedFields,
                'phone_number' => $user->getPhoneNumber(),
                'phone_indicatif' => $user->getPhoneNumberIndicatif() // ✅ Log pour vérifier
            ]);

            return new JsonResponse([
                'success' => true,
                'step' => $step,
                'updated_fields' => $updatedFields,
                'user' => [
                    'id' => $user->getId(),
                    'completion_percentage' => $user->getCompletionPercentage(),
                    'is_profile_complete' => $user->isProfileComplete(),
                    'missing_fields' => $user->getMissingFields(),
                    // ✅ AJOUT: Retourner les valeurs sauvegardées pour debug
                    'phone_number' => $user->getPhoneNumber(),
                    'phone_number_indicatif' => $user->getPhoneNumberIndicatif(),
                ]
            ]);

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

        // ✅ CORRECTION: Ajouter phoneNumberIndicatif à l'étape 0
        $stepFieldsMap = [
            0 => ['firstName', 'lastName', 'phoneNumber', 'phoneNumberIndicatif', 'profilePicture'], // ✅ AJOUTÉ
            1 => ['identityCardType', 'identityCard', 'selfieWithId'],
            2 => ['incomeSource', 'occupation', 'incomeProof', 'ownershipProof'],
            3 => ['termsAccepted', 'privacyAccepted', 'marketingAccepted'],
        ];

        $allowedFields = $stepFieldsMap[$step] ?? [];

        foreach ($allowedFields as $field) {
            if (!isset($data[$field])) {
                // ✅ AJOUT: Log pour debug les champs manquants
                if ($field === 'phoneNumberIndicatif') {
                    $this->logger->warning("phoneNumberIndicatif not found in data", [
                        'step' => $step,
                        'data_keys' => array_keys($data),
                        'expected_field' => $field
                    ]);
                }
                continue;
            }

            $setter = 'set' . ucfirst($field);
            $getter = 'get' . ucfirst($field);

            // Pour les booléens, utiliser is au lieu de get
            if (in_array($field, ['termsAccepted', 'privacyAccepted', 'marketingAccepted'])) {
                $getter = 'is' . ucfirst($field);
            }

            if (method_exists($user, $setter) && method_exists($user, $getter)) {
                $currentValue = $user->$getter();
                $newValue = $data[$field];

                // Pour les champs texte, nettoyer les espaces (sauf pour les URLs de fichiers)
                if (is_string($newValue) && !in_array($field, ['profilePicture', 'identityCard', 'selfieWithId', 'incomeProof', 'ownershipProof'])) {
                    $newValue = trim($newValue) ?: null;
                }

                // ✅ AJOUT: Log spécial pour phoneNumberIndicatif
                if ($field === 'phoneNumberIndicatif') {
                    $this->logger->info("Updating phoneNumberIndicatif", [
                        'current_value' => $currentValue,
                        'new_value' => $newValue,
                        'field' => $field
                    ]);
                }

                if ($currentValue !== $newValue) {
                    $user->$setter($newValue);
                    $updatedFields[] = $field;

                    // ✅ AJOUT: Confirmation de la mise à jour
                    $this->logger->info("Field updated", [
                        'field' => $field,
                        'old_value' => $currentValue,
                        'new_value' => $newValue
                    ]);
                }
            } else {
                // ✅ AJOUT: Log si les méthodes n'existent pas
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
                    'phone_number' => $user->getPhoneNumber(), // ✅ AJOUT
                    'phone_number_indicatif' => $user->getPhoneNumberIndicatif(), // ✅ AJOUT
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
}
