<?php

namespace App\Controller\Api;

use App\DTO\Profile\DocumentUploadRequest;
use App\Entity\Document;
use App\Entity\User;
use App\Repository\DocumentRepository;
use App\Service\Document\DocumentValidationService;
use App\Service\Upload\FileUploadService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/documents')]
#[IsGranted('ROLE_USER')]
class DocumentValidationController extends AbstractController
{
    public function __construct(
        private DocumentRepository $documentRepository,
        private DocumentValidationService $validationService,
        private FileUploadService $fileUploadService
    ) {}

    #[Route('', name: 'api_documents_list', methods: ['GET'])]
    public function list(
        #[CurrentUser] User $user
    ): JsonResponse {
        $documents = $this->documentRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );

        return $this->json([
            'documents' => $documents,
            'summary' => $this->documentRepository->getUserDocumentSummary($user)
        ], context: ['groups' => ['document:list']]);
    }

    #[Route('/upload', name: 'api_documents_upload', methods: ['POST'])]
    public function upload(
        Request $request,
        #[CurrentUser] User $user
    ): JsonResponse {
        $file = $request->files->get('document');
        $type = $request->request->get('type');
        $subType = $request->request->get('subType');

        if (!$file) {
            return $this->json([
                'error' => 'Aucun fichier fourni'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$type) {
            return $this->json([
                'error' => 'Type de document requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Valider le fichier
        $validationErrors = $this->validationService->validateUploadedFile($file, $type);
        if (!empty($validationErrors)) {
            return $this->json([
                'errors' => $validationErrors
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Upload du fichier
            $uploadResult = $this->fileUploadService->upload($file, $type, (string)$user->getId());

            // Créer le document
            $document = $this->validationService->createDocument(
                $user,
                $file,
                $type,
                $subType
            );

            // Mettre à jour le chemin du fichier
            $document->setFilePath($uploadResult['path']);

            // Ajouter les métadonnées optionnelles
            if ($request->request->has('title')) {
                $document->setTitle($request->request->get('title'));
            }
            if ($request->request->has('description')) {
                $document->setDescription($request->request->get('description'));
            }
            if ($request->request->has('documentNumber')) {
                $document->setDocumentNumber($request->request->get('documentNumber'));
            }
            if ($request->request->has('issuingAuthority')) {
                $document->setIssuingAuthority($request->request->get('issuingAuthority'));
            }
            if ($request->request->has('issueDate')) {
                $document->setIssueDate(new \DateTimeImmutable($request->request->get('issueDate')));
            }
            if ($request->request->has('expiresAt')) {
                $document->setExpiresAt(new \DateTimeImmutable($request->request->get('expiresAt')));
            }

            $this->documentRepository->save($document, true);

            return $this->json([
                'message' => 'Document uploadé avec succès',
                'document' => $document
            ], Response::HTTP_CREATED, context: ['groups' => ['document:read']]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de l\'upload : ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'api_documents_show', methods: ['GET'])]
    public function show(
        Document $document,
        #[CurrentUser] User $user
    ): JsonResponse {
        // Vérifier que le document appartient à l'utilisateur
        if ($document->getUser()->getId() !== $user->getId() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        return $this->json($document, context: ['groups' => ['document:read', 'document:detail']]);
    }

    #[Route('/{id}', name: 'api_documents_delete', methods: ['DELETE'])]
    public function delete(
        Document $document,
        #[CurrentUser] User $user
    ): JsonResponse {
        // Vérifier que le document appartient à l'utilisateur
        if ($document->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        // Vérifier que le document peut être supprimé
        if (!$document->canBeDeleted()) {
            return $this->json([
                'error' => 'Ce document ne peut pas être supprimé'
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $this->validationService->deleteDocument($document);

            return $this->json([
                'message' => 'Document supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la suppression : ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/check-requirements', name: 'api_documents_check_requirements', methods: ['GET'])]
    public function checkRequirements(
        #[CurrentUser] User $user
    ): JsonResponse {
        $requirements = $this->validationService->checkRequiredDocuments($user);

        return $this->json($requirements);
    }

    #[Route('/types', name: 'api_documents_types', methods: ['GET'])]
    public function getTypes(): JsonResponse
    {
        return $this->json([
            'identity' => [
                'types' => [
                    ['value' => 'identity_card', 'label' => 'Carte d\'identité'],
                    ['value' => 'passport', 'label' => 'Passeport'],
                    ['value' => 'driver_license', 'label' => 'Permis de conduire']
                ],
                'subtypes' => [
                    ['value' => 'recto', 'label' => 'Recto'],
                    ['value' => 'verso', 'label' => 'Verso'],
                    ['value' => 'selfie', 'label' => 'Selfie avec document']
                ]
            ],
            'financial' => [
                'types' => [
                    ['value' => 'bank_statement', 'label' => 'Relevé bancaire'],
                    ['value' => 'payslip', 'label' => 'Bulletin de salaire'],
                    ['value' => 'tax_return', 'label' => 'Avis d\'imposition'],
                    ['value' => 'employment_contract', 'label' => 'Contrat de travail']
                ]
            ]
        ]);
    }
}
