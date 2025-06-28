<?php

namespace App\Service\Admin;

use App\Repository\UserRepository;
use App\Repository\MessageRepository;
use App\Repository\EmailLogRepository;

class DashboardStatsService
{
    public function __construct(
        private UserRepository $userRepository,
        private MessageRepository $messageRepository,
        private EmailLogRepository $emailLogRepository
    ) {}

    public function getStats(): array
    {
        return [
            'total_users' => $this->userRepository->count([]),
            'pending_verification' => $this->userRepository->count(['status' => 'pending_verification']),
            'active_users' => $this->userRepository->count(['status' => 'active']),
            'unread_messages' => count($this->messageRepository->findUnreadForAdmin()),
            'failed_emails' => count($this->emailLogRepository->findFailedEmails()),
            'total_messages' => $this->messageRepository->count([]),
        ];
    }

    public function getPendingUsers(int $limit = 5): array
    {
        return $this->userRepository->findBy(
            ['status' => 'pending_verification'],
            ['createdAt' => 'DESC'],
            $limit
        );
    }

    public function getRecentMessages(int $limit = 5): array
    {
        return $this->messageRepository->findBy(
            ['isRead' => false, 'type' => 'user_to_admin'],
            ['createdAt' => 'DESC'],
            $limit
        );
    }
}
