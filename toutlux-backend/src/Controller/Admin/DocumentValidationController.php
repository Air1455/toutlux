<?php

namespace App\Controller\Admin;

use App\Entity\Document;
use App\Repository\DocumentRepository;
use App\Service\Document\DocumentValidationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\User;

#[Route('/documents')]
#[IsGranted('ROLE_ADMIN')]
class DocumentValidationController extends AbstractController
{
    public function __construct(
        private DocumentRepository $documentRepository,
        private DocumentValidationService $validationService
    ) {}

    #[Route('/pending', name: 'admin_documents_pending')]
    public function pending(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 20;

        $documents = $this->documentRepository->findPending($limit * $page);
        $totalPending = $this->documentRepository->countByStatus(\App\Enum\DocumentStatus::PENDING);

        return $this->render('admin/document/pending.html.twig', [
            'documents' => $documents,
            'totalPending' => $totalPending,
            'page' => $page,
            'pages' => ceil($totalPending / $limit)
        ]);
    }

    #[Route('/{id}/validate', name: 'admin_document_validate', methods: ['GET', 'POST'])]
    public function validate(
        Document $document,
        Request $request,
        #[CurrentUser] User $admin
    ): Response {
        if ($document->getStatus() !== \App\Enum\DocumentStatus::PENDING) {
            $this->addFlash('warning', 'Ce document a déjà été traité.');
            return $this->redirectToRoute('admin_documents_pending');
        }

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');
            $notes = $request->request->get('notes', '');

            try {
                if ($action === 'approve') {
                    $this->validationService->validateDocument($document, $admin, $notes);
                    $this->addFlash('success', 'Document validé avec succès.');
                } elseif ($action === 'reject') {
                    $reason = $request->request->get('reason', 'Document non conforme');
                    $this->validationService->rejectDocument($document, $admin, $reason, $notes);
                    $this->addFlash('success', 'Document rejeté.');
                }

                return $this->redirectToRoute('admin_documents_pending');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la validation : ' . $e->getMessage());
            }
        }

        return $this->render('admin/document/validate.html.twig', [
            'document' => $document
        ]);
    }

    #[Route('/{id}/reject', name: 'admin_document_reject', methods: ['POST'])]
    public function reject(
        Document $document,
        Request $request,
        #[CurrentUser] User $admin
    ): Response {
        if ($document->getStatus() !== \App\Enum\DocumentStatus::PENDING) {
            return $this->json(['error' => 'Document déjà traité'], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);
        $reason = $data['reason'] ?? 'Document non conforme';
        $notes = $data['notes'] ?? '';

        try {
            $this->validationService->rejectDocument($document, $admin, $reason, $notes);

            return $this->json([
                'success' => true,
                'message' => 'Document rejeté avec succès'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors du rejet : ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/stats', name: 'admin_documents_stats')]
    public function stats(): Response
    {
        $stats = $this->validationService->getValidationStats();

        return $this->render('admin/document/stats.html.twig', [
            'stats' => $stats
        ]);
    }

    #[Route('/search', name: 'admin_documents_search')]
    public function search(Request $request): Response
    {
        $query = $request->query->get('q', '');
        $type = $request->query->get('type');
        $status = $request->query->get('status');

        $criteria = [];

        if ($type) {
            $criteria['type'] = $type;
        }

        if ($status) {
            $criteria['status'] = $status;
        }

        $documents = $this->documentRepository->findBy($criteria, ['createdAt' => 'DESC'], 50);

        return $this->render('admin/document/search.html.twig', [
            'documents' => $documents,
            'query' => $query,
            'filters' => [
                'type' => $type,
                'status' => $status
            ]
        ]);
    }
}
