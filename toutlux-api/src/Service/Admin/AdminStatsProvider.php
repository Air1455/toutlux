<?php

namespace App\Service\Admin;

use App\Repository\UserRepository;
use App\Repository\MessageRepository;
use App\Repository\EmailLogRepository;
use App\Repository\HouseRepository;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service pour fournir les statistiques globales à l'administration
 * Utilisé dans le template base pour afficher les notifications
 */
class AdminStatsProvider
{
    private ?array $cachedStats = null;

    public function __construct(
        private UserRepository $userRepository,
        private MessageRepository $messageRepository,
        private EmailLogRepository $emailLogRepository,
        private HouseRepository $houseRepository,
        private RequestStack $requestStack
    ) {}

    /**
     * Récupère les statistiques pour l'affichage dans la navbar/sidebar
     */
    public function getStats(): array
    {
        // Cache les stats pour la durée de la requête
        if ($this->cachedStats !== null) {
            return $this->cachedStats;
        }

        $this->cachedStats = [
            'total_users' => $this->userRepository->count([]),
            'active_users' => $this->userRepository->count(['status' => 'active']),
            'pending_verification' => $this->userRepository->count(['status' => 'pending_verification']),
            'suspended_users' => $this->userRepository->count(['status' => 'suspended']),

            'unread_messages' => count($this->messageRepository->findUnreadForAdmin()),
            'total_messages' => $this->messageRepository->count([]),

            'total_houses' => $this->houseRepository->count([]),
            'active_houses' => $this->houseRepository->count(['status' => 'active']),

            'failed_emails' => count($this->emailLogRepository->findFailedEmails()),

            'pending_identity_docs' => $this->userRepository->countPendingIdentityValidation(),
            'pending_financial_docs' => $this->userRepository->countPendingFinancialValidation(),
            'fully_verified_users' => $this->userRepository->countFullyVerified(),
        ];

        return $this->cachedStats;
    }

    /**
     * Récupère uniquement les compteurs de notifications
     */
    public function getNotificationCounts(): array
    {
        $stats = $this->getStats();

        return [
            'messages' => $stats['unread_messages'],
            'users' => $stats['pending_verification'],
            'identity_docs' => $stats['pending_identity_docs'],
            'financial_docs' => $stats['pending_financial_docs'],
            'failed_emails' => $stats['failed_emails'],
            'total' => $stats['unread_messages'] +
                $stats['pending_verification'] +
                $stats['pending_identity_docs'] +
                $stats['pending_financial_docs']
        ];
    }

    /**
     * Réinitialise le cache (utile après des actions admin)
     */
    public function clearCache(): void
    {
        $this->cachedStats = null;
    }
}
