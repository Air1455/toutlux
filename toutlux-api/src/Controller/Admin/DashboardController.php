<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Message;
use App\Entity\House;
use App\Repository\UserRepository;
use App\Repository\MessageRepository;
use App\Repository\EmailLogRepository;
use App\Repository\HouseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'admin_dashboard')]
    public function index(
        UserRepository $userRepository,
        MessageRepository $messageRepository,
        EmailLogRepository $emailLogRepository,
        HouseRepository $houseRepository
    ): Response {
        // Statistiques générales
        $stats = [
            'total_users' => $userRepository->count([]),
            'active_users' => $userRepository->count(['status' => 'active']),
            'pending_verification' => $userRepository->count(['status' => 'pending_verification']),
            'suspended_users' => $userRepository->count(['status' => 'suspended']),

            'unread_messages' => count($messageRepository->findUnreadForAdmin()),
            'total_messages' => $messageRepository->count([]),

            'total_houses' => $houseRepository->count([]),
            'active_houses' => $houseRepository->count(['status' => 'active']),

            'failed_emails' => count($emailLogRepository->findFailedEmails()),

            // Nouvelles stats
            'pending_identity_docs' => $userRepository->countPendingIdentityValidation(),
            'pending_financial_docs' => $userRepository->countPendingFinancialValidation(),
            'fully_verified_users' => $userRepository->countFullyVerified(),

            // Stats du jour
            'registrations_today' => $userRepository->countRegistrationsToday(),
            'registrations_week' => $userRepository->countRegistrationsThisWeek(),
            'registrations_month' => $userRepository->countRegistrationsThisMonth(),
        ];

        // Graphiques pour le dashboard
        $chartData = [
            'monthly_registrations' => $userRepository->getMonthlyRegistrationStats(6),
            'verification_stats' => $userRepository->getVerificationStats(),
            'user_types' => $userRepository->getUserTypeStats(),
            'profile_completion' => $userRepository->getProfileCompletionStats(),
        ];

        // Utilisateurs en attente de validation
        $pendingUsers = $userRepository->findPendingValidation(10);

        // Messages récents non lus
        $recentMessages = $messageRepository->findBy(
            ['isRead' => false, 'type' => 'user_to_admin'],
            ['createdAt' => 'DESC'],
            5
        );

        // Activité récente
        $recentActivity = [
            'recent_users' => $userRepository->findBy(
                [],
                ['createdAt' => 'DESC'],
                5
            ),
            'recent_validations' => $userRepository->findRecentValidations(5),
            'recently_active' => $userRepository->findRecentlyActive(7, 5),
        ];

        // Alertes
        $alerts = [];

        if ($stats['failed_emails'] > 0) {
            $alerts[] = [
                'type' => 'danger',
                'message' => sprintf('%d emails ont échoué', $stats['failed_emails']),
                'link' => $this->generateUrl('admin_email_logs'),
                'action' => 'Voir les logs'
            ];
        }

        if ($stats['pending_identity_docs'] > 5) {
            $alerts[] = [
                'type' => 'warning',
                'message' => sprintf('%d documents d\'identité en attente', $stats['pending_identity_docs']),
                'link' => $this->generateUrl('admin_documents', ['type' => 'identity']),
                'action' => 'Examiner'
            ];
        }

        if ($stats['suspended_users'] > 0) {
            $alerts[] = [
                'type' => 'info',
                'message' => sprintf('%d utilisateurs suspendus', $stats['suspended_users']),
                'link' => $this->generateUrl('admin_users', ['status' => 'suspended']),
                'action' => 'Gérer'
            ];
        }

        return $this->render('admin/dashboard/index.html.twig', [
            'stats' => $stats,
            'chart_data' => $chartData,
            'pending_users' => $pendingUsers,
            'recent_messages' => $recentMessages,
            'recent_activity' => $recentActivity,
            'alerts' => $alerts,
        ]);
    }

    #[Route('/stats/export', name: 'admin_stats_export', methods: ['GET'])]
    public function exportStats(
        UserRepository $userRepository,
        HouseRepository $houseRepository
    ): Response {
        // Export CSV des statistiques
        $data = [
            ['Métrique', 'Valeur', 'Date'],
            ['Utilisateurs total', $userRepository->count([]), date('Y-m-d')],
            ['Utilisateurs actifs', $userRepository->count(['status' => 'active']), date('Y-m-d')],
            ['Annonces total', $houseRepository->count([]), date('Y-m-d')],
            ['Profils complets', $userRepository->countFullyVerified(), date('Y-m-d')],
        ];

        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="stats_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        fclose($output);

        return $response;
    }
}
