<?php

namespace App\Controller\Api;

use App\Entity\Document;
use App\Entity\User;
use App\Repository\DocumentRepository;
use App\Service\Document\DocumentValidationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/documents')]
#[IsGranted('ROLE_USER')]
class DocumentValidationController extends AbstractController
{
    public function __construct(
        private DocumentValidationService $documentValidationService,
        private DocumentRepository $documentRepository
    ) {}

    #[Route('', name: 'api_documents_list', methods: ['GET'])]
    public function list(#[CurrentUser] User $user): JsonResponse
    {
        $documents = $this->documentRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );

        $documentsData = array_map(function(Document $doc) {
            return [
                'id' => $doc->getId(),
                'type' => $doc->getType(),
                'subType' => $doc->getSubType(),
                'fileName' => $doc->getFileName(),
                'fileSize' => $doc->getFileSize(),
                'status' => $doc->getStatus(),
                'uploadedAt' => $doc->getCreatedAt()->format('c'),
                'validatedAt' => $doc->getValidatedAt()?->format('c'),
                'rejectionReason' => $doc->getRejectionReason(),
                'url' => '/uploads/' . $doc->getFilePath()
            ];
        }, $documents);

        return $this->json([
            'documents' => $documentsData,
            'count' => count($documents),
            'required' => $this->documentValidationService->checkRequiredDocuments($user)
        ]);
    }

    #[Route('/upload', name: 'api_documents_upload', methods: ['POST'])]
    public function upload(
        Request $request,
        #[CurrentUser] User $user
    ): JsonResponse {
        $type = $request->request->get('type');
        $subType = $request->request->get('subType');
        $file = $request->files->get('document');

        if (!$file instanceof UploadedFile) {
            return $this->json([
                'error' => 'Aucun fichier fourni'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!in_array($type, ['identity', 'financial'])) {
            return $this->json([
                'error' => 'Type de document invalide'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Valider le fichier
        $validationErrors = $this->documentValidationService->validateUploadedFile($file, $type);
        if (!empty($validationErrors)) {
            return $this->json([
                'errors' => $validationErrors
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Créer le document
            $document = $this->documentValidationService->createDocument(
                $user,
                $file,
                $type,
                $subType
            );

            return $this->json([
                'message' => 'Document uploadé avec succès',
                'document' => [
                    'id' => $document->getId(),
                    'type' => $document->getType(),
                    'subType' => $document->getSubType(),
                    'fileName' => $document->getFileName(),
                    'status' => $document->getStatus(),
                    'uploadedAt' => $document->getCreatedAt()->format('c')
                ]
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de l\'upload : ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'api_documents_get', methods: ['GET'])]
    public function get(
        Document $document,
        #[CurrentUser] User $user
    ): JsonResponse {
        // Vérifier que le document appartient à l'utilisateur
        if ($document->getUser()->getId() !== $user->getId()) {
            return $this->json([
                'error' => 'Document non trouvé'
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'document' => [
                'id' => $document->getId(),
                'type' => $document->getType(),
                'subType' => $document->getSubType(),
                'fileName' => $document->getFileName(),
                'fileSize' => $document->getFileSize(),
                'mimeType' => $document->getMimeType(),
                'status' => $document->getStatus(),
                'uploadedAt' => $document->getCreatedAt()->format('c'),
                'validatedAt' => $document->getValidatedAt()?->format('c'),
                'validatedBy' => $document->getValidatedBy()?->getFullName(),
                'rejectionReason' => $document->getRejectionReason(),
                'validationNotes' => $document->getValidationNotes(),
                'url' => '/uploads/' . $document->getFilePath()
            ]
        ]);
    }

    #[Route('/{id}', name: 'api_documents_delete', methods: ['DELETE'])]
    public function delete(
        Document $document,
        #[CurrentUser] User $user
    ): JsonResponse {
        // Vérifier que le document appartient à l'utilisateur
        if ($document->getUser()->getId() !== $user->getId()) {
            return $this->json([
                'error' => 'Document non trouvé'
            ], Response::HTTP_NOT_FOUND);
        }

        // Ne pas permettre la suppression des documents validés
        if ($document->getStatus() === 'validated') {
            return $this->json([
                'error' => 'Impossible de supprimer un document validé'
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $this->documentValidationService->deleteDocument($document);

            return $this->json([
                'message' => 'Document supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la suppression'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/status', name: 'api_documents_status', methods: ['GET'])]
    public function status(#[CurrentUser] User $user): JsonResponse
    {
        $requiredDocs = $this->documentValidationService->checkRequiredDocuments($user);
        $documents = $this->documentRepository->findBy(['user' => $user]);

        $stats = [
            'total' => count($documents),
            'pending' => 0,
            'validated' => 0,
            'rejected' => 0
        ];

        foreach ($documents as $doc) {
            switch ($doc->getStatus()) {
                case 'pending':
                    $stats['pending']++;
                    break;
                case 'validated':
                    $stats['validated']++;
                    break;
                case 'rejected':
                    $stats['rejected']++;
                    break;
            }
        }

        return $this->json([
            'stats' => $stats,
            'required' => $requiredDocs,
            'trustScore' => $user->getTrustScore()
        ]);
    }
}
