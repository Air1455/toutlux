<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\User\UserWorkflowService;
use App\Service\Messaging\MessageService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class UserManagementController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em,
        private UserWorkflowService $userWorkflowService,
        private MessageService $messageService,
        private PaginatorInterface $paginator
    ) {}

    #[Route('', name: 'admin_users')]
    public function index(Request $request): Response
    {
        $filters = [
            'status' => $request->query->get('status'),
            'verification' => $request->query->get('verification'),
            'search' => $request->query->get('search'),
            'has_documents' => $request->query->get('has_documents'),
            'user_type' => $request->query->get('user_type'),
            'date_from' => $request->query->get('date_from'),
            'date_to' => $request->query->get('date_to'),
        ];

        $queryBuilder = $this->userRepository->createQueryBuilder('u');

        // Application des filtres
        if ($filters['status']) {
            $queryBuilder->andWhere('u.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if ($filters['verification']) {
            switch ($filters['verification']) {
                case 'email_not_verified':
                    $queryBuilder->andWhere('u.isEmailVerified = false');
                    break;
                case 'phone_not_verified':
                    $queryBuilder->andWhere('u.isPhoneVerified = false');
                    break;
                case 'identity_pending':
                    $queryBuilder->andWhere('u.identityCard IS NOT NULL')
                        ->andWhere('u.isIdentityVerified = false');
                    break;
                case 'financial_pending':
                    $queryBuilder->andWhere('(u.incomeProof IS NOT NULL OR u.ownershipProof IS NOT NULL)')
                        ->andWhere('u.isFinancialDocsVerified = false');
                    break;
                case 'fully_verified':
                    $queryBuilder->andWhere('u.isEmailVerified = true')
                        ->andWhere('u.isPhoneVerified = true')
                        ->andWhere('u.isIdentityVerified = true')
                        ->andWhere('u.isFinancialDocsVerified = true');
                    break;
            }
        }

        if ($filters['search']) {
            $queryBuilder->andWhere('u.firstName LIKE :search OR u.lastName LIKE :search OR u.email LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if ($filters['user_type']) {
            $queryBuilder->andWhere('u.userType = :userType')
                ->setParameter('userType', $filters['user_type']);
        }

        if ($filters['date_from']) {
            $queryBuilder->andWhere('u.createdAt >= :dateFrom')
                ->setParameter('dateFrom', new \DateTime($filters['date_from']));
        }

        if ($filters['date_to']) {
            $queryBuilder->andWhere('u.createdAt <= :dateTo')
                ->setParameter('dateTo', new \DateTime($filters['date_to'] . ' 23:59:59'));
        }

        $queryBuilder->orderBy('u.createdAt', 'DESC');

        // Pagination
        $pagination = $this->paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            20
        );

        // Stats pour les filtres
        $filterStats = [
            'total' => $this->userRepository->count([]),
            'pending_verification' => $this->userRepository->count(['status' => 'pending_verification']),
            'active' => $this->userRepository->count(['status' => 'active']),
            'suspended' => $this->userRepository->count(['status' => 'suspended']),
            'pending_identity' => $this->userRepository->countPendingIdentityValidation(),
            'pending_financial' => $this->userRepository->countPendingFinancialValidation(),
        ];

        return $this->render('admin/users/index.html.twig', [
            'pagination' => $pagination,
            'filters' => $filters,
            'filter_stats' => $filterStats,
        ]);
    }

    #[Route('/{id}', name: 'admin_user_show', requirements: ['id' => '\d+'])]
    public function show(User $user): Response
    {
        // Historique des actions
        $actionHistory = [];

        if ($user->getEmailVerifiedAt()) {
            $actionHistory[] = [
                'type' => 'email_verified',
                'date' => $user->getEmailVerifiedAt(),
                'label' => 'Email vérifié'
            ];
        }

        if ($user->getIdentityVerifiedAt()) {
            $actionHistory[] = [
                'type' => 'identity_verified',
                'date' => $user->getIdentityVerifiedAt(),
                'label' => 'Identité vérifiée'
            ];
        }

        if ($user->getFinancialDocsVerifiedAt()) {
            $actionHistory[] = [
                'type' => 'financial_verified',
                'date' => $user->getFinancialDocsVerifiedAt(),
                'label' => 'Documents financiers vérifiés'
            ];
        }

        // Trier par date décroissante
        usort($actionHistory, function($a, $b) {
            return $b['date'] <=> $a['date'];
        });

        // Messages de l'utilisateur
        $userMessages = $user->getMessages()->slice(0, 5);

        // Annonces de l'utilisateur
        $userHouses = $user->getHouses()->slice(0, 5);

        return $this->render('admin/users/show.html.twig', [
            'user' => $user,
            'completion_percentage' => $user->getCompletionPercentage(),
            'verification_score' => $user->getVerificationScore(),
            'pending_validations' => $user->getPendingValidationsCount(),
            'documents_status' => $user->getDocumentsStatus(),
            'is_profile_complete' => $user->isProfileComplete(),
            'action_history' => $actionHistory,
            'user_messages' => $userMessages,
            'user_houses' => $userHouses,
            'validation_status' => $user->getDetailedValidationStatus(),
        ]);
    }

    #[Route('/{id}/approve-email', name: 'admin_user_approve_email', methods: ['POST'])]
    public function approveEmail(User $user, Request $request): Response
    {
        // Vérification CSRF
        if (!$this->isCsrfTokenValid('approve-email-' . $user->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $user->setIsEmailVerified(true);
        $user->setStatus('email_confirmed');
        $this->em->flush();

        // Déclenche le workflow pour l'email de notification
        $this->userWorkflowService->handleEmailConfirmation($user);

        $this->addFlash('success', 'Email validé et utilisateur notifié');
        return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
    }

    #[Route('/{id}/approve-documents', name: 'admin_user_approve_documents', methods: ['POST'])]
    public function approveDocuments(User $user, Request $request): Response
    {
        // Vérification CSRF
        if (!$this->isCsrfTokenValid('approve-documents-' . $user->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $documentType = $request->request->get('document_type', 'all');

        if ($documentType === 'identity' || $documentType === 'all') {
            $user->setIsIdentityVerified(true);
        }

        if ($documentType === 'financial' || $documentType === 'all') {
            $user->setIsFinancialDocsVerified(true);
        }

        // Si tous les documents sont approuvés
        if ($user->isIdentityVerified() && $user->isFinancialDocsVerified()) {
            $user->setStatus('documents_approved');
        }

        $this->em->flush();

        // Déclenche le workflow pour l'email de notification
        $this->userWorkflowService->handleDocumentsApproval($user);

        $this->addFlash('success', 'Documents approuvés et utilisateur notifié');
        return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
    }

    #[Route('/{id}/reject-documents', name: 'admin_user_reject_documents', methods: ['POST'])]
    public function rejectDocuments(User $user, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('reject-documents-' . $user->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $reason = $request->request->get('reason', 'Documents non conformes');
        $documentType = $request->request->get('document_type');

        // Créer un message système avec la raison du rejet
        $subject = match($documentType) {
            'identity' => 'Documents d\'identité rejetés',
            'financial' => 'Documents financiers rejetés',
            default => 'Documents rejetés'
        };

        $this->messageService->createMessage(
            $user,
            $subject,
            "Vos documents ont été rejetés pour la raison suivante : " . $reason .
            "\n\nVeuillez soumettre de nouveaux documents conformes.",
            \App\Enum\MessageType::ADMIN_TO_USER
        );

        // Ajouter la raison dans les metadata
        $metadata = $user->getMetadata();
        $metadata['last_rejection'] = [
            'type' => $documentType,
            'reason' => $reason,
            'date' => (new \DateTime())->format('Y-m-d H:i:s'),
            'admin' => $this->getUser()->getEmail()
        ];
        $user->setMetadata($metadata);

        $this->em->flush();

        $this->addFlash('warning', 'Documents rejetés et utilisateur notifié');
        return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
    }

    #[Route('/{id}/suspend', name: 'admin_user_suspend', methods: ['POST'])]
    public function suspend(User $user, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('suspend-' . $user->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $reason = $request->request->get('reason', 'Suspension administrative');
        $duration = $request->request->get('duration'); // En jours

        $user->setStatus('suspended');

        // Ajouter les détails de suspension dans metadata
        $metadata = $user->getMetadata();
        $metadata['suspension'] = [
            'reason' => $reason,
            'date' => (new \DateTime())->format('Y-m-d H:i:s'),
            'duration' => $duration,
            'expires_at' => $duration ? (new \DateTime("+{$duration} days"))->format('Y-m-d H:i:s') : null,
            'admin' => $this->getUser()->getEmail()
        ];
        $user->setMetadata($metadata);

        $this->em->flush();

        // Créer un message système
        $message = "Votre compte a été suspendu pour la raison suivante : " . $reason;
        if ($duration) {
            $message .= "\n\nDurée de la suspension : {$duration} jours.";
        }

        $this->messageService->createMessage(
            $user,
            'Compte suspendu',
            $message,
            \App\Enum\MessageType::ADMIN_TO_USER
        );

        $this->addFlash('warning', 'Utilisateur suspendu');
        return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
    }

    #[Route('/{id}/activate', name: 'admin_user_activate', methods: ['POST'])]
    public function activate(User $user, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('activate-' . $user->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $user->setStatus('active');

        // Retirer les infos de suspension
        $metadata = $user->getMetadata();
        unset($metadata['suspension']);
        $user->setMetadata($metadata);

        $this->em->flush();

        // Notifier l'utilisateur
        $this->messageService->createMessage(
            $user,
            'Compte réactivé',
            'Votre compte a été réactivé. Vous pouvez à nouveau utiliser tous les services de la plateforme.',
            \App\Enum\MessageType::ADMIN_TO_USER
        );

        $this->addFlash('success', 'Utilisateur activé');
        return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
    }

    #[Route('/{id}/export', name: 'admin_user_export')]
    public function exportUserData(User $user): Response
    {
        $data = $this->userRepository->exportUserData($user);

        $response = new JsonResponse($data);
        $response->headers->set('Content-Disposition',
            sprintf('attachment; filename="user_%d_data_%s.json"',
                $user->getId(),
                date('Y-m-d')
            )
        );

        return $response;
    }

    #[Route('/bulk-action', name: 'admin_users_bulk_action', methods: ['POST'])]
    public function bulkAction(Request $request): Response
    {
        $action = $request->request->get('action');
        $userIds = $request->request->all('user_ids');

        if (empty($userIds)) {
            $this->addFlash('error', 'Aucun utilisateur sélectionné');
            return $this->redirectToRoute('admin_users');
        }

        $users = $this->userRepository->findBy(['id' => $userIds]);

        switch ($action) {
            case 'activate':
                foreach ($users as $user) {
                    $user->setStatus('active');
                }
                $this->addFlash('success', sprintf('%d utilisateurs activés', count($users)));
                break;

            case 'suspend':
                foreach ($users as $user) {
                    $user->setStatus('suspended');
                }
                $this->addFlash('warning', sprintf('%d utilisateurs suspendus', count($users)));
                break;

            case 'export':
                // Export CSV des utilisateurs sélectionnés
                return $this->exportUsersCsv($users);

            default:
                $this->addFlash('error', 'Action non reconnue');
        }

        $this->em->flush();

        return $this->redirectToRoute('admin_users');
    }

    private function exportUsersCsv(array $users): Response
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="users_export_' . date('Y-m-d') . '.csv"');

        // Ajout du BOM pour Excel
        $response->setContent("\xEF\xBB\xBF");

        $handle = fopen('php://temp', 'r+');

        // En-têtes
        fputcsv($handle, [
            'ID', 'Email', 'Nom', 'Prénom', 'Téléphone', 'Type', 'Statut',
            'Email vérifié', 'Téléphone vérifié', 'Identité vérifiée', 'Docs financiers vérifiés',
            'Date inscription', 'Dernière activité'
        ]);

        // Données
        foreach ($users as $user) {
            fputcsv($handle, [
                $user->getId(),
                $user->getEmail(),
                $user->getLastName(),
                $user->getFirstName(),
                $user->getPhoneNumber(),
                $user->getUserType(),
                $user->getStatus(),
                $user->isEmailVerified() ? 'Oui' : 'Non',
                $user->isPhoneVerified() ? 'Oui' : 'Non',
                $user->isIdentityVerified() ? 'Oui' : 'Non',
                $user->isFinancialDocsVerified() ? 'Oui' : 'Non',
                $user->getCreatedAt()->format('Y-m-d H:i'),
                $user->getLastActiveAt() ? $user->getLastActiveAt()->format('Y-m-d H:i') : 'N/A'
            ]);
        }

        rewind($handle);
        $response->setContent($response->getContent() . stream_get_contents($handle));
        fclose($handle);

        return $response;
    }
}
