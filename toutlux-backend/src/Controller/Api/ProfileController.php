<?php

namespace App\Controller\Api;

use App\Entity\Document;
use App\Entity\UserProfile;
use App\Service\Document\DocumentValidationService;
use App\Service\User\TrustScoreCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Entity\User;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/profile', name: 'api_profile_')]
class ProfileController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private TrustScoreCalculator $trustScoreCalculator,
        private DocumentValidationService $documentValidationService
    ) {}

    #[Route('', name: 'get', methods: ['GET'])]
    public function getProfile(#[CurrentUser] User $user): JsonResponse
    {
        $profile = $user->getProfile();

        if (!$profile) {
            return $this->json([
                'error' => 'Profile not found'
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id' => $profile->getId(),
            'firstName' => $profile->getFirstName(),
            'lastName' => $profile->getLastName(),
            'phoneNumber' => $profile->getPhoneNumber(),
            'profilePictureUrl' => $profile->getProfilePictureUrl(),
            'personalInfoValidated' => $profile->isPersonalInfoValidated(),
            'identityValidated' => $profile->isIdentityValidated(),
            'financialValidated' => $profile->isFinancialValidated(),
            'termsAccepted' => $profile->isTermsAccepted(),
            'termsAcceptedAt' => $profile->getTermsAcceptedAt()?->format('Y-m-d H:i:s'),
            'completionPercentage' => $profile->getCompletionPercentage(),
            'trustScore' => $user->getTrustScore(),
            'trustScoreBreakdown' => $this->trustScoreCalculator->getScoreBreakdown($user),
            'nextSteps' => $this->trustScoreCalculator->getNextSteps($user)
        ]);
    }

    #[Route('', name: 'update', methods: ['PUT', 'PATCH'])]
    public function updateProfile(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $profile = $user->getProfile();

        if (!$profile) {
            $profile = new UserProfile();
            $user->setProfile($profile);
        }

        $data = json_decode($request->getContent(), true);

        // Update personal information
        if (isset($data['firstName'])) {
            $profile->setFirstName($data['firstName']);
        }
        if (isset($data['lastName'])) {
            $profile->setLastName($data['lastName']);
        }
        if (isset($data['phoneNumber'])) {
            $profile->setPhoneNumber($data['phoneNumber']);
        }

        // Handle terms acceptance
        if (isset($data['termsAccepted']) && $data['termsAccepted'] === true) {
            $profile->setTermsAccepted(true);
        }

        // Validate profile
        $errors = $this->validator->validate($profile);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }

            return $this->json([
                'errors' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        // Check if personal info is complete for validation
        if ($profile->isPersonalInfoComplete() && !$profile->isPersonalInfoValidated()) {
            $profile->setPersonalInfoValidated(true);
        }

        $this->entityManager->flush();

        // Update trust score
        $this->trustScoreCalculator->updateUserTrustScore($user);

        return $this->json([
            'message' => 'Profile updated successfully',
            'profile' => [
                'firstName' => $profile->getFirstName(),
                'lastName' => $profile->getLastName(),
                'phoneNumber' => $profile->getPhoneNumber(),
                'completionPercentage' => $profile->getCompletionPercentage(),
                'trustScore' => $user->getTrustScore()
            ]
        ]);
    }

    #[Route('/avatar', name: 'upload_avatar', methods: ['POST'])]
    public function uploadAvatar(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $profile = $user->getProfile();

        if (!$profile) {
            $profile = new UserProfile();
            $user->setProfile($profile);
        }

        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $request->files->get('avatar');

        if (!$uploadedFile) {
            return $this->json([
                'error' => 'No file uploaded'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validate file
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($uploadedFile->getMimeType(), $allowedMimeTypes)) {
            return $this->json([
                'error' => 'Invalid file type. Allowed types: JPEG, PNG, WebP'
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($uploadedFile->getSize() > 5 * 1024 * 1024) { // 5MB
            return $this->json([
                'error' => 'File too large. Maximum size: 5MB'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $profile->setProfilePictureFile($uploadedFile);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Avatar uploaded successfully',
                'profilePictureUrl' => $profile->getProfilePictureUrl()
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to upload avatar: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/documents', name: 'get_documents', methods: ['GET'])]
    public function getDocuments(#[CurrentUser] User $user): JsonResponse
    {
        $documents = $user->getDocuments()->toArray();

        $documentsData = array_map(function(Document $document) {
            return [
                'id' => $document->getId(),
                'type' => $document->getType(),
                'typeLabel' => $document->getTypeLabel(),
                'status' => $document->getStatus(),
                'fileUrl' => $document->getFileUrl(),
                'fileSize' => $document->getFileSize(),
                'rejectionReason' => $document->getRejectionReason(),
                'createdAt' => $document->getCreatedAt()->format('Y-m-d H:i:s'),
                'validatedAt' => $document->getValidatedAt()?->format('Y-m-d H:i:s')
            ];
        }, $documents);

        return $this->json($documentsData);
    }

    #[Route('/documents', name: 'upload_document', methods: ['POST'])]
    public function uploadDocument(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $type = $request->request->get('type');

        if (!in_array($type, [Document::TYPE_IDENTITY, Document::TYPE_SELFIE, Document::TYPE_FINANCIAL])) {
            return $this->json([
                'error' => 'Invalid document type'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Check if user can submit this document type
        if (!$this->documentValidationService->canUserSubmitDocument($user, $type)) {
            return $this->json([
                'error' => 'You already have a pending or approved document of this type'
            ], Response::HTTP_CONFLICT);
        }

        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $request->files->get('document');

        if (!$uploadedFile) {
            return $this->json([
                'error' => 'No file uploaded'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validate file
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
        if (!in_array($uploadedFile->getMimeType(), $allowedMimeTypes)) {
            return $this->json([
                'error' => 'Invalid file type. Allowed types: JPEG, PNG, WebP, PDF'
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($uploadedFile->getSize() > 10 * 1024 * 1024) { // 10MB
            return $this->json([
                'error' => 'File too large. Maximum size: 10MB'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $document = new Document();
            $document->setType($type);
            $document->setUser($user);
            $document->setFile($uploadedFile);

            $this->entityManager->persist($document);
            $this->entityManager->flush();

            // Submit for validation
            $this->documentValidationService->submitDocument($document);

            return $this->json([
                'message' => 'Document uploaded successfully',
                'document' => [
                    'id' => $document->getId(),
                    'type' => $document->getType(),
                    'typeLabel' => $document->getTypeLabel(),
                    'status' => $document->getStatus(),
                    'fileUrl' => $document->getFileUrl(),
                    'createdAt' => $document->getCreatedAt()->format('Y-m-d H:i:s')
                ]
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to upload document: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/documents/{id}', name: 'delete_document', methods: ['DELETE'])]
    public function deleteDocument(#[CurrentUser] User $user, string $id): JsonResponse
    {
        $document = $this->entityManager->getRepository(Document::class)->find($id);

        if (!$document) {
            return $this->json([
                'error' => 'Document not found'
            ], Response::HTTP_NOT_FOUND);
        }

        // Check ownership
        if ($document->getUser() !== $user) {
            return $this->json([
                'error' => 'Access denied'
            ], Response::HTTP_FORBIDDEN);
        }

        // Can only delete pending documents
        if (!$document->isPending()) {
            return $this->json([
                'error' => 'Can only delete pending documents'
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->remove($document);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Document deleted successfully'
        ]);
    }

    #[Route('/trust-score', name: 'trust_score', methods: ['GET'])]
    public function getTrustScore(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json([
            'trustScore' => $user->getTrustScore(),
            'maxScore' => 5.0,
            'breakdown' => $this->trustScoreCalculator->getScoreBreakdown($user),
            'nextSteps' => $this->trustScoreCalculator->getNextSteps($user)
        ]);
    }

    #[Route('/validate-section/{section}', name: 'validate_section', methods: ['POST'])]
    public function validateSection(#[CurrentUser] User $user, string $section): JsonResponse
    {
        $profile = $user->getProfile();

        if (!$profile) {
            return $this->json([
                'error' => 'Profile not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $validSections = ['personal_info', 'identity', 'financial', 'terms'];

        if (!in_array($section, $validSections)) {
            return $this->json([
                'error' => 'Invalid section'
            ], Response::HTTP_BAD_REQUEST);
        }

        $isValid = false;
        $message = '';

        switch ($section) {
            case 'personal_info':
                if ($profile->isPersonalInfoComplete()) {
                    $profile->setPersonalInfoValidated(true);
                    $isValid = true;
                    $message = 'Personal information validated';
                } else {
                    $message = 'Please complete all personal information fields';
                }
                break;

            case 'identity':
                $identityDoc = $this->documentValidationService->getLatestDocumentByType($user, Document::TYPE_IDENTITY);
                $selfieDoc = $this->documentValidationService->getLatestDocumentByType($user, Document::TYPE_SELFIE);

                if ($identityDoc && $identityDoc->isApproved() && $selfieDoc && $selfieDoc->isApproved()) {
                    $isValid = true;
                    $message = 'Identity documents already validated';
                } else {
                    $message = 'Please upload and wait for approval of identity documents';
                }
                break;

            case 'financial':
                $financialDoc = $this->documentValidationService->getLatestDocumentByType($user, Document::TYPE_FINANCIAL);

                if ($financialDoc && $financialDoc->isApproved()) {
                    $isValid = true;
                    $message = 'Financial documents already validated';
                } else {
                    $message = 'Please upload and wait for approval of financial documents';
                }
                break;

            case 'terms':
                if ($profile->isTermsAccepted()) {
                    $isValid = true;
                    $message = 'Terms already accepted';
                } else {
                    $message = 'Please accept the terms and conditions';
                }
                break;
        }

        if ($isValid) {
            $this->entityManager->flush();
            $this->trustScoreCalculator->updateUserTrustScore($user);
        }

        return $this->json([
            'section' => $section,
            'valid' => $isValid,
            'message' => $message,
            'trustScore' => $user->getTrustScore()
        ]);
    }
}
