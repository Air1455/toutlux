<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\User\UserWorkflowService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/documents')]
#[IsGranted('ROLE_ADMIN')]
class DocumentsController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em,
        private UserWorkflowService $userWorkflowService,
        private PaginatorInterface $paginator
    ) {}

    #[Route('', name: 'admin_documents')]
    public function index(Request $request): Response
    {
        $type = $request->query->get('type', 'all');
        $status = $request->query->get('status', 'pending');

        $queryBuilder = $this->userRepository->createQueryBuilder('u');

        // Filtrer par type de document
        switch ($type) {
            case 'identity':
                $queryBuilder->andWhere('u.identityCard IS NOT NULL')
                    ->andWhere('u.selfieWithId IS NOT NULL');
                if ($status === 'pending') {
                    $queryBuilder->andWhere('u.isIdentityVerified = false');
                } elseif ($status === 'approved') {
                    $queryBuilder->andWhere('u.isIdentityVerified = true');
                }
                break;

            case 'financial':
                $queryBuilder->andWhere('(u.incomeProof IS NOT NULL OR u.ownershipProof IS NOT NULL)');
                if ($status === 'pending') {
                    $queryBuilder->andWhere('u.isFinancialDocsVerified = false');
                } elseif ($status === 'approved') {
                    $queryBuilder->andWhere('u.isFinancialDocsVerified = true');
                }
                break;

            default: // 'all'
                $queryBuilder->andWhere('(u.identityCard IS NOT NULL OR u.incomeProof IS NOT NULL OR u.ownershipProof IS NOT NULL)');
                if ($status === 'pending') {
                    $queryBuilder->andWhere('(
                        (u.identityCard IS NOT NULL AND u.isIdentityVerified = false) OR
                        ((u.incomeProof IS NOT NULL OR u.ownershipProof IS NOT NULL) AND u.isFinancialDocsVerified = false)
                    )');
                }
        }

        // Tri par date de soumission
        $queryBuilder->orderBy('u.updatedAt', 'DESC');

        // Pagination
        $pagination = $this->paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10
        );

        // Statistiques
        $stats = [
            'total_pending' => $this->userRepository->countPendingIdentityValidation() +
                $this->userRepository->countPendingFinancialValidation(),
            'identity_pending' => $this->userRepository->countPendingIdentityValidation(),
            'financial_pending' => $this->userRepository->countPendingFinancialValidation(),
            'approved_today' => $this->getApprovedTodayCount(),
        ];

        return $this->render('admin/documents/index.html.twig', [
            'pagination' => $pagination,
            'current_type' => $type,
            'current_status' => $status,
            'stats' => $stats,
        ]);
    }

    #[Route('/review/{id}', name: 'admin_documents_review')]
    public function review(User $user): Response
    {
        if (!$user->hasIdentityDocuments() && !$user->hasFinancialDocuments()) {
            $this->addFlash('error', 'Cet utilisateur n\'a pas soumis de documents');
            return $this->redirectToRoute('admin_documents');
        }

        // Historique des rejets s'il y en a
        $rejectionHistory = [];
        $metadata = $user->getMetadata();

        if (isset($metadata['last_rejection'])) {
            $rejectionHistory[] = $metadata['last_rejection'];
        }

        return $this->render('admin/documents/review.html.twig', [
            'user' => $user,
            'documents_status' => $user->getDocumentsStatus(),
            'rejection_history' => $rejectionHistory,
        ]);
    }

    #[Route('/batch-approve', name: 'admin_documents_batch_approve', methods: ['POST'])]
    public function batchApprove(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('batch-approve', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $userIds = $request->request->all('user_ids');
        $documentType = $request->request->get('document_type', 'all');

        if (empty($userIds)) {
            $this->addFlash('error', 'Aucun utilisateur sélectionné');
            return $this->redirectToRoute('admin_documents');
        }

        $users = $this->userRepository->findBy(['id' => $userIds]);
        $approvedCount = 0;

        foreach ($users as $user) {
            if ($documentType === 'identity' || $documentType === 'all') {
                if ($user->hasIdentityDocuments() && !$user->isIdentityVerified()) {
                    $user->setIsIdentityVerified(true);
                    $approvedCount++;
                }
            }

            if ($documentType === 'financial' || $documentType === 'all') {
                if ($user->hasFinancialDocuments() && !$user->isFinancialDocsVerified()) {
                    $user->setIsFinancialDocsVerified(true);
                    $approvedCount++;
                }
            }

            // Si tous les documents sont vérifiés, mettre à jour le statut
            if ($user->isIdentityVerified() && $user->isFinancialDocsVerified()) {
                $user->setStatus('documents_approved');
                $this->userWorkflowService->handleDocumentsApproval($user);
            }
        }

        $this->em->flush();

        $this->addFlash('success', sprintf('%d documents approuvés', $approvedCount));
        return $this->redirectToRoute('admin_documents');
    }

    #[Route('/stats', name: 'admin_documents_stats')]
    public function stats(): Response
    {
        $stats = [
            'by_type' => [
                'identity' => [
                    'total' => $this->countUsersWithIdentityDocs(),
                    'pending' => $this->userRepository->countPendingIdentityValidation(),
                    'approved' => $this->countApprovedIdentityDocs(),
                    'rejected' => $this->countRejectedDocs('identity'),
                ],
                'financial' => [
                    'total' => $this->countUsersWithFinancialDocs(),
                    'pending' => $this->userRepository->countPendingFinancialValidation(),
                    'approved' => $this->countApprovedFinancialDocs(),
                    'rejected' => $this->countRejectedDocs('financial'),
                ],
            ],
            'average_processing_time' => $this->calculateAverageProcessingTime(),
            'daily_submissions' => $this->getDailySubmissionsStats(),
            'rejection_reasons' => $this->getTopRejectionReasons(),
        ];

        return $this->render('admin/documents/stats.html.twig', [
            'stats' => $stats,
        ]);
    }

    private function getApprovedTodayCount(): int
    {
        $today = new \DateTime('today');

        $qb = $this->userRepository->createQueryBuilder('u');
        $count = $qb->select('COUNT(u.id)')
            ->where('u.identityVerifiedAt >= :today OR u.financialDocsVerifiedAt >= :today')
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count;
    }

    private function countUsersWithIdentityDocs(): int
    {
        $qb = $this->userRepository->createQueryBuilder('u');
        return $qb->select('COUNT(u.id)')
            ->where('u.identityCard IS NOT NULL')
            ->andWhere('u.selfieWithId IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function countUsersWithFinancialDocs(): int
    {
        $qb = $this->userRepository->createQueryBuilder('u');
        return $qb->select('COUNT(u.id)')
            ->where('u.incomeProof IS NOT NULL OR u.ownershipProof IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function countApprovedIdentityDocs(): int
    {
        return $this->userRepository->count(['isIdentityVerified' => true]);
    }

    private function countApprovedFinancialDocs(): int
    {
        return $this->userRepository->count(['isFinancialDocsVerified' => true]);
    }

    private function countRejectedDocs(string $type): int
    {
        $qb = $this->userRepository->createQueryBuilder('u');
        return $qb->select('COUNT(u.id)')
            ->where("JSON_EXTRACT(u.metadata, '$.last_rejection.type') = :type")
            ->setParameter('type', $type)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function calculateAverageProcessingTime(): array
    {
        // Calcul du temps moyen de traitement pour chaque type
        $result = $this->em->createQuery('
            SELECT
                AVG(TIMESTAMPDIFF(HOUR, u.updatedAt, u.identityVerifiedAt)) as identity_avg,
                AVG(TIMESTAMPDIFF(HOUR, u.updatedAt, u.financialDocsVerifiedAt)) as financial_avg
            FROM App\Entity\User u
            WHERE u.identityVerifiedAt IS NOT NULL OR u.financialDocsVerifiedAt IS NOT NULL
        ')->getSingleResult();

        return [
            'identity' => round($result['identity_avg'] ?? 0, 1),
            'financial' => round($result['financial_avg'] ?? 0, 1),
        ];
    }

    private function getDailySubmissionsStats(): array
    {
        $stats = [];
        $today = new \DateTime();

        for ($i = 6; $i >= 0; $i--) {
            $date = clone $today;
            $date->modify("-{$i} days");
            $dateStr = $date->format('Y-m-d');

            $qb = $this->userRepository->createQueryBuilder('u');
            $count = $qb->select('COUNT(u.id)')
                ->where('DATE(u.updatedAt) = :date')
                ->andWhere('u.identityCard IS NOT NULL OR u.incomeProof IS NOT NULL')
                ->setParameter('date', $dateStr)
                ->getQuery()
                ->getSingleScalarResult();

            $stats[$dateStr] = (int) $count;
        }

        return $stats;
    }

    private function getTopRejectionReasons(): array
    {
        $qb = $this->userRepository->createQueryBuilder('u');
        $results = $qb->select("JSON_EXTRACT(u.metadata, '$.last_rejection.reason') as reason, COUNT(u.id) as count")
            ->where("JSON_EXTRACT(u.metadata, '$.last_rejection') IS NOT NULL")
            ->groupBy('reason')
            ->orderBy('count', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $reasons = [];
        foreach ($results as $result) {
            if ($result['reason']) {
                $reasons[trim($result['reason'], '"')] = $result['count'];
            }
        }

        return $reasons;
    }
}
