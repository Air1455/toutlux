<?php

namespace App\Service\Document;

use App\Entity\Document;
use App\Entity\User;
use App\Entity\Notification;
use App\Service\Notification\NotificationService;
use App\Service\Email\EmailService;
use App\Service\User\TrustScoreCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class DocumentValidationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationService $notificationService,
        private EmailService $emailService,
        private TrustScoreCalculator $trustScoreCalculator,
        private LoggerInterface $logger
    ) {}

    public function submitDocument(Document $document): void
    {
        // Notify admins of new document
        $this->notifyAdminsOfNewDocument($document);

        // Log submission
        $this->logger->info('Document submitted for validation', [
            'documentId' => $document->getId(),
            'userId' => $document->getUser()->getId(),
            'type' => $document->getType()
        ]);
    }

    public function validateDocument(Document $document, User $validator, bool $approve, ?string $rejectionReason = null): void
    {
        $user = $document->getUser();
        $profile = $user->getProfile();

        if ($approve) {
            $document->approve($validator);

            // Update profile validation status based on document type
            switch ($document->getType()) {
                case Document::TYPE_IDENTITY:
                case Document::TYPE_SELFIE:
                    // Both identity and selfie must be approved for identity validation
                    if ($this->areIdentityDocumentsComplete($user)) {
                        $profile->setIdentityValidated(true);
                    }
                    break;

                case Document::TYPE_FINANCIAL:
                    $profile->setFinancialValidated(true);
                    break;
            }

            // Create approval notification
            $this->notificationService->createNotification(
                $user,
                Notification::TYPE_DOCUMENT_APPROVED,
                'Document Approved',
                sprintf('Your %s has been approved.', $document->getTypeLabel()),
                ['documentId' => $document->getId()->toRfc4122()]
            );

            // Send approval email
            $this->emailService->sendEmail(
                $user->getEmail(),
                'Document Approved',
                'emails/document_approved.html.twig',
                [
                    'user' => $user,
                    'document' => $document
                ]
            );

            $this->logger->info('Document approved', [
                'documentId' => $document->getId(),
                'validatorId' => $validator->getId()
            ]);

        } else {
            if (!$rejectionReason) {
                throw new \InvalidArgumentException('Rejection reason is required');
            }

            $document->reject($validator, $rejectionReason);

            // Create rejection notification
            $this->notificationService->createNotification(
                $user,
                Notification::TYPE_DOCUMENT_REJECTED,
                'Document Rejected',
                sprintf('Your %s has been rejected: %s', $document->getTypeLabel(), $rejectionReason),
                ['documentId' => $document->getId()->toRfc4122()]
            );

            // Send rejection email
            $this->emailService->sendEmail(
                $user->getEmail(),
                'Document Rejected',
                'emails/document_rejected.html.twig',
                [
                    'user' => $user,
                    'document' => $document,
                    'reason' => $rejectionReason
                ]
            );

            $this->logger->info('Document rejected', [
                'documentId' => $document->getId(),
                'validatorId' => $validator->getId(),
                'reason' => $rejectionReason
            ]);
        }

        $this->entityManager->flush();

        // Update trust score
        $this->trustScoreCalculator->updateUserTrustScore($user);
    }

    private function areIdentityDocumentsComplete(User $user): bool
    {
        $documents = $user->getDocuments();

        $hasApprovedIdentity = false;
        $hasApprovedSelfie = false;

        foreach ($documents as $document) {
            if ($document->isApproved()) {
                if ($document->getType() === Document::TYPE_IDENTITY) {
                    $hasApprovedIdentity = true;
                } elseif ($document->getType() === Document::TYPE_SELFIE) {
                    $hasApprovedSelfie = true;
                }
            }
        }

        return $hasApprovedIdentity && $hasApprovedSelfie;
    }

    private function notifyAdminsOfNewDocument(Document $document): void
    {
        // Get all admin users
        $admins = $this->entityManager->getRepository(User::class)->findByRole('ROLE_ADMIN');

        foreach ($admins as $admin) {
            // Create notification
            $notification = new Notification();
            $notification->setUser($admin);
            $notification->setType(Notification::TYPE_DOCUMENT_SUBMITTED);
            $notification->setTitle('New Document for Validation');
            $notification->setContent(sprintf(
                '%s submitted a %s for validation.',
                $document->getUser()->getFullName() ?? $document->getUser()->getEmail(),
                $document->getTypeLabel()
            ));
            $notification->setData([
                'documentId' => $document->getId()->toRfc4122(),
                'userId' => $document->getUser()->getId()->toRfc4122(),
                'adminAction' => true
            ]);

            $this->entityManager->persist($notification);
        }

        $this->entityManager->flush();

        // Send email to primary admin
        if (!empty($admins)) {
            $this->emailService->sendEmail(
                $admins[0]->getEmail(),
                'New Document Pending Validation',
                'emails/admin/document_validation.html.twig',
                [
                    'document' => $document,
                    'user' => $document->getUser(),
                    'validationUrl' => '/admin/documents/' . $document->getId() . '/validate'
                ]
            );
        }
    }

    public function getPendingDocumentsCount(): int
    {
        return $this->entityManager->getRepository(Document::class)
            ->count(['status' => Document::STATUS_PENDING]);
    }

    public function getPendingDocuments(): array
    {
        return $this->entityManager->getRepository(Document::class)
            ->findBy(['status' => Document::STATUS_PENDING], ['createdAt' => 'ASC']);
    }

    public function getDocumentValidationStats(): array
    {
        $repository = $this->entityManager->getRepository(Document::class);

        return [
            'pending' => $repository->count(['status' => Document::STATUS_PENDING]),
            'approved' => $repository->count(['status' => Document::STATUS_APPROVED]),
            'rejected' => $repository->count(['status' => Document::STATUS_REJECTED]),
            'total' => $repository->count([]),
            'by_type' => [
                'identity' => $repository->count(['type' => Document::TYPE_IDENTITY]),
                'selfie' => $repository->count(['type' => Document::TYPE_SELFIE]),
                'financial' => $repository->count(['type' => Document::TYPE_FINANCIAL]),
            ]
        ];
    }

    public function canUserSubmitDocument(User $user, string $documentType): bool
    {
        // Check if user already has a pending or approved document of this type
        $existingDocument = $this->entityManager->getRepository(Document::class)
            ->findOneBy([
                'user' => $user,
                'type' => $documentType,
                'status' => [Document::STATUS_PENDING, Document::STATUS_APPROVED]
            ]);

        return $existingDocument === null;
    }

    public function getUserDocumentsByType(User $user, string $type): array
    {
        return $this->entityManager->getRepository(Document::class)
            ->findBy([
                'user' => $user,
                'type' => $type
            ], ['createdAt' => 'DESC']);
    }

    public function getLatestDocumentByType(User $user, string $type): ?Document
    {
        return $this->entityManager->getRepository(Document::class)
            ->findOneBy([
                'user' => $user,
                'type' => $type
            ], ['createdAt' => 'DESC']);
    }
}
