<?php

namespace App\Controller\Admin;

use App\Entity\Document;
use App\Service\Document\DocumentValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/documents', name: 'admin_document_')]
#[IsGranted('ROLE_ADMIN')]
class DocumentValidationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DocumentValidationService $validationService
    ) {}

    #[Route('/validation', name: 'validation')]
    public function index(Request $request): Response
    {
        $filter = $request->query->get('filter', 'pending');
        $page = $request->query->getInt('page', 1);
        $limit = 20;

        $repository = $this->entityManager->getRepository(Document::class);

        $queryBuilder = $repository->createQueryBuilder('d')
            ->join('d.user', 'u')
            ->orderBy('d.createdAt', 'ASC');

        switch ($filter) {
            case 'pending':
                $queryBuilder->where('d.status = :status')
                    ->setParameter('status', Document::STATUS_PENDING);
                break;
            case 'approved':
                $queryBuilder->where('d.status = :status')
                    ->setParameter('status', Document::STATUS_APPROVED);
                break;
            case 'rejected':
                $queryBuilder->where('d.status = :status')
                    ->setParameter('status', Document::STATUS_REJECTED);
                break;
        }

        $paginator = $this->paginate($queryBuilder->getQuery(), $page, $limit);
        $documents = $paginator['items'];
        $totalPages = $paginator['totalPages'];

        return $this->render('admin/document/validation.html.twig', [
            'documents' => $documents,
            'filter' => $filter,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'stats' => $this->validationService->getDocumentValidationStats()
        ]);
    }

    #[Route('/{id}/validate', name: 'validate', methods: ['GET', 'POST'])]
    public function validate(Request $request, Document $document): Response
    {
        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');

            if ($action === 'approve') {
                $this->validationService->validateDocument(
                    $document,
                    $this->getUser(),
                    true
                );

                $this->addFlash('success', sprintf(
                    'Document %s approved successfully',
                    $document->getTypeLabel()
                ));
            } elseif ($action === 'reject') {
                $reason = $request->request->get('rejection_reason');

                if (empty($reason)) {
                    $this->addFlash('error', 'Rejection reason is required');
                    return $this->redirectToRoute('admin_document_validate', ['id' => $document->getId()]);
                }

                $this->validationService->validateDocument(
                    $document,
                    $this->getUser(),
                    false,
                    $reason
                );

                $this->addFlash('warning', sprintf(
                    'Document %s rejected',
                    $document->getTypeLabel()
                ));
            }

            return $this->redirectToRoute('admin_document_validation');
        }

        return $this->render('admin/document/validate.html.twig', [
            'document' => $document,
            'user' => $document->getUser(),
            'profile' => $document->getUser()->getProfile(),
            'otherDocuments' => $this->getOtherUserDocuments($document)
        ]);
    }

    #[Route('/{id}/preview', name: 'preview')]
    public function preview(Document $document): Response
    {
        // Security check
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $filePath = $this->getParameter('kernel.project_dir') . '/public' . $document->getFileUrl();

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Document file not found');
        }

        $mimeType = mime_content_type($filePath);
        $content = file_get_contents($filePath);

        return new Response($content, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($filePath) . '"'
        ]);
    }

    #[Route('/batch-action', name: 'batch_action', methods: ['POST'])]
    public function batchAction(Request $request): Response
    {
        $action = $request->request->get('action');
        $documentIds = $request->request->all('documents');

        if (empty($documentIds)) {
            $this->addFlash('error', 'No documents selected');
            return $this->redirectToRoute('admin_document_validation');
        }

        $documents = $this->entityManager->getRepository(Document::class)
            ->findBy(['id' => $documentIds]);

        $count = 0;
        foreach ($documents as $document) {
            if ($document->getStatus() === Document::STATUS_PENDING) {
                if ($action === 'approve') {
                    $this->validationService->validateDocument(
                        $document,
                        $this->getUser(),
                        true
                    );
                    $count++;
                }
            }
        }

        $this->addFlash('success', sprintf('%d documents approved', $count));

        return $this->redirectToRoute('admin_document_validation');
    }

    private function getOtherUserDocuments(Document $document): array
    {
        return $this->entityManager->getRepository(Document::class)
            ->findBy(
                ['user' => $document->getUser(), 'id' => ['!=' => $document->getId()]],
                ['createdAt' => 'DESC']
            );
    }

    private function paginate($query, int $page, int $limit): array
    {
        $totalItems = count($query->getResult());
        $totalPages = ceil($totalItems / $limit);

        $query->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return [
            'items' => $query->getResult(),
            'totalPages' => $totalPages,
            'totalItems' => $totalItems
        ];
    }
}
