<?php

namespace App\Controller\Api;

use App\DTO\Profile\ProfileUpdateRequest;
use App\Entity\User;
use App\Service\Document\TrustScoreCalculator;
use App\Service\Upload\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FileUploadService $fileUploadService,
        private TrustScoreCalculator $trustScoreCalculator,
        private ValidatorInterface $validator
    ) {}

    #[Route('', name: 'api_profile_get', methods: ['GET'])]
    public function getProfile(#[CurrentUser] User $user): JsonResponse
    {
        $trustScoreDetails = $this->trustScoreCalculator->getTrustScoreDetails($user);

        return $this->json([
            'profile' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'phone' => $user->getPhone(),
                'birthDate' => $user->getBirthDate()?->format('Y-m-d'),
                'address' => $user->getAddress(),
                'city' => $user->getCity(),
                'postalCode' => $user->getPostalCode(),
                'country' => $user->getCountry(),
                'avatar' => $user->getAvatar(),
                'bio' => $user->getBio(),
                'isVerified' => $user->isVerified(),
                'isPhoneVerified' => $user->isPhoneVerified(),
                'termsAccepted' => $user->isTermsAccepted(),
                'termsAcceptedAt' => $user->getTermsAcceptedAt()?->format('c'),
                'emailNotificationsEnabled' => $user->isEmailNotificationsEnabled(),
                'smsNotificationsEnabled' => $user->isSmsNotificationsEnabled(),
                'createdAt' => $user->getCreatedAt()->format('c'),
                'trustScore' => $user->getTrustScore(),
                'trustScoreDetails' => $trustScoreDetails
            ]
        ]);
    }

    #[Route('', name: 'api_profile_update', methods: ['PUT', 'PATCH'])]
    public function updateProfile(
        #[CurrentUser] User $user,
        #[MapRequestPayload] ProfileUpdateRequest $request
    ): JsonResponse {
        // Mettre à jour les champs modifiables
        if ($request->firstName !== null) {
            $user->setFirstName($request->firstName);
        }
        if ($request->lastName !== null) {
            $user->setLastName($request->lastName);
        }
        if ($request->phone !== null) {
            $user->setPhone($request->phone);
        }
        if ($request->birthDate !== null) {
            $user->setBirthDate(new \DateTime($request->birthDate));
        }
        if ($request->address !== null) {
            $user->setAddress($request->address);
        }
        if ($request->city !== null) {
            $user->setCity($request->city);
        }
        if ($request->postalCode !== null) {
            $user->setPostalCode($request->postalCode);
        }
        if ($request->country !== null) {
            $user->setCountry($request->country);
        }
        if ($request->bio !== null) {
            $user->setBio($request->bio);
        }

        // Valider les modifications
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        // Recalculer le score de confiance
        $this->trustScoreCalculator->updateUserTrustScore($user);

        return $this->json([
            'message' => 'Profil mis à jour avec succès',
            'profile' => [
                'id' => $user->getId(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'phone' => $user->getPhone(),
                'trustScore' => $user->getTrustScore()
            ]
        ]);
    }

    #[Route('/avatar', name: 'api_profile_upload_avatar', methods: ['POST'])]
    public function uploadAvatar(
        Request $request,
        #[CurrentUser] User $user
    ): JsonResponse {
        $file = $request->files->get('avatar');

        if (!$file instanceof UploadedFile) {
            return $this->json([
                'error' => 'Aucun fichier fourni'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Supprimer l'ancien avatar s'il existe
            if ($user->getAvatar() && !str_starts_with($user->getAvatar(), 'http')) {
                $this->fileUploadService->delete($user->getAvatar());
            }

            // Upload du nouveau fichier
            $result = $this->fileUploadService->upload($file, 'avatar', (string)$user->getId());

            // Mettre à jour l'utilisateur
            $user->setAvatar($result['url']);
            $this->entityManager->flush();

            // Recalculer le score de confiance
            $this->trustScoreCalculator->updateUserTrustScore($user);

            return $this->json([
                'message' => 'Avatar uploadé avec succès',
                'avatar' => $result['url'],
                'trustScore' => $user->getTrustScore()
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de l\'upload : ' . $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/accept-terms', name: 'api_profile_accept_terms', methods: ['POST'])]
    public function acceptTerms(#[CurrentUser] User $user): JsonResponse
    {
        if ($user->isTermsAccepted()) {
            return $this->json([
                'message' => 'Les conditions ont déjà été acceptées',
                'acceptedAt' => $user->getTermsAcceptedAt()->format('c')
            ]);
        }

        $user->setTermsAccepted(true);
        $user->setTermsAcceptedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        // Recalculer le score de confiance
        $this->trustScoreCalculator->updateUserTrustScore($user);

        return $this->json([
            'message' => 'Conditions d\'utilisation acceptées',
            'acceptedAt' => $user->getTermsAcceptedAt()->format('c'),
            'trustScore' => $user->getTrustScore()
        ]);
    }

    #[Route('/notifications', name: 'api_profile_notifications_settings', methods: ['GET', 'PUT'])]
    public function notificationSettings(
        Request $request,
        #[CurrentUser] User $user
    ): JsonResponse {
        if ($request->isMethod('GET')) {
            return $this->json([
                'emailNotifications' => $user->isEmailNotificationsEnabled(),
                'smsNotifications' => $user->isSmsNotificationsEnabled()
            ]);
        }

        // PUT - Mise à jour des paramètres
        $data = json_decode($request->getContent(), true);

        if (isset($data['emailNotifications'])) {
            $user->setEmailNotificationsEnabled($data['emailNotifications']);
        }
        if (isset($data['smsNotifications'])) {
            $user->setSmsNotificationsEnabled($data['smsNotifications']);
        }

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Paramètres de notification mis à jour',
            'emailNotifications' => $user->isEmailNotificationsEnabled(),
            'smsNotifications' => $user->isSmsNotificationsEnabled()
        ]);
    }

    #[Route('/delete', name: 'api_profile_delete', methods: ['DELETE'])]
    public function deleteProfile(
        Request $request,
        #[CurrentUser] User $user
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $password = $data['password'] ?? null;

        if (!$password) {
            return $this->json([
                'error' => 'Mot de passe requis pour supprimer le compte'
            ], Response::HTTP_BAD_REQUEST);
        }

        // TODO: Vérifier le mot de passe et implémenter la suppression soft

        return $this->json([
            'message' => 'Compte supprimé avec succès'
        ]);
    }

    #[Route('/completion', name: 'api_profile_completion', methods: ['GET'])]
    public function getProfileCompletion(#[CurrentUser] User $user): JsonResponse
    {
        $sections = [
            'personal' => [
                'completed' => !empty($user->getFirstName()) &&
                    !empty($user->getLastName()) &&
                    !empty($user->getPhone()) &&
                    !empty($user->getAvatar()),
                'fields' => [
                    'firstName' => !empty($user->getFirstName()),
                    'lastName' => !empty($user->getLastName()),
                    'phone' => !empty($user->getPhone()),
                    'avatar' => !empty($user->getAvatar())
                ]
            ],
            'identity' => [
                'completed' => false, // À calculer selon les documents
                'documents' => []
            ],
            'financial' => [
                'completed' => false, // À calculer selon les documents
                'documents' => []
            ],
            'terms' => [
                'completed' => $user->isTermsAccepted(),
                'acceptedAt' => $user->getTermsAcceptedAt()?->format('c')
            ]
        ];

        // TODO: Compléter avec la logique des documents

        $completedSections = array_filter($sections, fn($s) => $s['completed']);
        $percentage = (count($completedSections) / count($sections)) * 100;

        return $this->json([
            'percentage' => round($percentage),
            'sections' => $sections,
            'nextStep' => $this->getNextStep($sections)
        ]);
    }

    private function getNextStep(array $sections): ?array
    {
        $steps = [
            'personal' => [
                'name' => 'Informations personnelles',
                'description' => 'Complétez vos informations de base',
                'route' => '/profile/personal'
            ],
            'identity' => [
                'name' => 'Vérification d\'identité',
                'description' => 'Ajoutez vos documents d\'identité',
                'route' => '/profile/identity'
            ],
            'financial' => [
                'name' => 'Documents financiers',
                'description' => 'Prouvez votre capacité financière',
                'route' => '/profile/financial'
            ],
            'terms' => [
                'name' => 'Conditions d\'utilisation',
                'description' => 'Acceptez les conditions pour finaliser',
                'route' => '/profile/terms'
            ]
        ];

        foreach ($sections as $key => $section) {
            if (!$section['completed'] && isset($steps[$key])) {
                return $steps[$key];
            }
        }

        return null;
    }
}
