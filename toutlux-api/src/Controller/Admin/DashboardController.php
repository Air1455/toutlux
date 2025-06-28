<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Message;
use App\Repository\UserRepository;
use App\Repository\MessageRepository;
use App\Repository\EmailLogRepository;
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
        EmailLogRepository $emailLogRepository
    ): Response {
        // Redirection si accès à /admin sans être connecté sera gérée par security.yaml

        // Statistiques rapides
        $stats = [
            'total_users' => $userRepository->count([]),
            'pending_verification' => $userRepository->count(['status' => 'pending_verification']),
            'unread_messages' => count($messageRepository->findUnreadForAdmin()),
            'failed_emails' => count($emailLogRepository->findFailedEmails()),
        ];

        // Utilisateurs récents en attente
        $pendingUsers = $userRepository->findBy(
            ['status' => 'pending_verification'],
            ['createdAt' => 'DESC'],
            5
        );

        // Messages récents non lus
        $recentMessages = $messageRepository->findBy(
            ['isRead' => false, 'type' => 'user_to_admin'],
            ['createdAt' => 'DESC'],
            5
        );

        return $this->render('admin/dashboard/index.html.twig', [
            'stats' => $stats,
            'pending_users' => $pendingUsers,
            'recent_messages' => $recentMessages,
        ]);
    }
}
