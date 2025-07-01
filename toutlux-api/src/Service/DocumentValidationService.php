<?php

namespace App\Service;

use App\Entity\User;
use App\Enum\MessageType;
use App\Service\Messaging\EmailService;
use App\Service\Messaging\MessageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class DocumentValidationService
{
    public function __construct(
        private EntityManagerInterface $em,
        private EmailService $emailService,
        private MessageService $messageService,
        private WorkflowInterface $userWorkflow,
    ) {}

    public function validateIdentityDocuments(User $user, string $adminEmail): bool
    {
        if (!$user->hasIdentityDocuments()) {
            throw new \LogicException('User has no identity documents to validate');
        }

        $user->setIsIdentityVerified(true);
        $user->setIdentityVerifiedAt(new \DateTimeImmutable());

        $metadata = $user->getMetadata();
        $metadata['identity_validation'] = [
            'validated_at' => new \DateTimeImmutable(),
            'validated_by' => $adminEmail,
        ];
        $user->setMetadata($metadata);

        $this->em->flush();

        // Notification
        $this->messageService->createMessage(
            $user,
            'Documents d\'identité validés',
            'Vos documents d\'identité ont été validés avec succès.',
            MessageType::SYSTEM
        );

        if ($user->isFullyVerified()) {
            $this->userWorkflow->apply($user, 'complete_verification');
            $this->emailService->sendDocumentsApprovedNotification($user);
        }

        return true;
    }

    public function rejectDocuments(User $user, string $type, string $reason, string $adminEmail): bool
    {
        $metadata = $user->getMetadata();
        $metadata['last_rejection'] = [
            'type' => $type,
            'reason' => $reason,
            'date' => new \DateTimeImmutable(),
            'admin' => $adminEmail,
        ];
        $user->setMetadata($metadata);

        $this->em->flush();

        // Notification
        $this->messageService->createMessage(
            $user,
            'Documents rejetés',
            "Vos documents ont été rejetés pour la raison suivante : $reason",
            MessageType::ADMIN_TO_USER
        );

        return true;
    }
}
