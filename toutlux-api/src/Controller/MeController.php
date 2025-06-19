<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Context\Normalizer\ObjectNormalizerContextBuilder;

class MeController extends AbstractController
{
    public function __construct(
        private readonly Security      $security,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if (!$user) {
            return new JsonResponse(['message' => 'Unauthorized'], 401);
        }

        // Mettre à jour lastActiveAt
        $user->setLastActiveAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        // Utiliser les groupes de sérialisation Symfony
        $context = (new ObjectNormalizerContextBuilder())
            ->withGroups(['user:read', 'user:private'])
            ->toArray();

        return $this->json($user, 200, [], $context);
    }

    #[Route('/api/me', name: 'api_me_update', methods: ['PUT', 'PATCH'])]
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if (!$user) {
            return new JsonResponse(['message' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse(['error' => 'Invalid JSON data'], 400);
        }

        // Liste des champs modifiables
        $allowedFields = [
            'firstName', 'lastName', 'phoneNumber', 'profilePicture',
            'userType', 'occupation', 'incomeSource', 'language',
            'identityCardType', 'identityCard', 'selfieWithId',
            'incomeProof', 'ownershipProof',
            'termsAccepted', 'privacyAccepted', 'marketingAccepted'
        ];

        $updatedFields = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $setter = 'set' . ucfirst($field);
                if (method_exists($user, $setter)) {
                    $currentValue = $user->{'get' . ucfirst($field)}();
                    if ($currentValue !== $data[$field]) {
                        $user->$setter($data[$field]);
                        $updatedFields[] = $field;
                    }
                }
            }
        }

        if (empty($updatedFields)) {
            return new JsonResponse([
                'message' => 'No changes detected',
                'user' => [
                    'id' => $user->getId(),
                    'completionPercentage' => $user->getCompletionPercentage()
                ]
            ]);
        }

        $user->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'updated_fields' => $updatedFields,
            'user' => [
                'id' => $user->getId(),
                'completionPercentage' => $user->getCompletionPercentage(),
                'isProfileComplete' => $user->isProfileComplete(),
                'missingFields' => $user->getMissingFields()
            ],
            'message' => 'Profile updated successfully'
        ]);
    }
}
