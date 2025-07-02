<?php

namespace App\Service\Document;

use App\Entity\Document;
use App\Entity\User;
use App\Service\Email\NotificationEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DocumentValidationService
{
    private const ALLOWED_MIME_TYPES = [
        'identity' => ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'],
        'financial' => ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'],
        'selfie' => ['image/jpeg', 'image/png', 'image/jpg']
    ];

    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB

    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationEmailService $notificationService,
        private TrustScoreCalculator $trustScoreCalculator,
        private ValidatorInterface $validator,
        private LoggerInterface $logger
    ) {}

    /**
     * Valider un fichier uploadé
     */
    public function validateUploadedFile(UploadedFile $file, string $documentType): array
    {
        $errors = [];

        // Vérifier la taille
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            $errors[] = sprintf('Le fichier ne doit pas dépasser %d MB', self::MAX_FILE_SIZE / 1024 / 1024);
        }

        // Vérifier le type MIME
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES[$documentType] ?? [])) {
            $errors[] = 'Type de fichier non autorisé. Formats acceptés : JPG, PNG, PDF';
        }

        // Vérifier si le fichier est corrompu
        if (!$file->isValid()) {
            $errors[] = 'Le fichier semble être corrompu';
        }

        return $errors;
    }

    /**
     * Créer un document depuis un fichier uploadé
     */
    public function createDocument(
        User $user,
        UploadedFile $file,
        string $type,
        string $subType = null
    ): Document {
        $document = new Document();
        $document->setUser($user);
        $document->setType($type);
        $document->setSubType($subType);
        $document->setFileName($file->getClientOriginalName());
        $document->setFileSize($file->getSize());
        $document->setMimeType($file->getMimeType());
        $document->setStatus('pending');

        // Générer un nom de fichier unique
        $fileName = sprintf(
            '%s_%s_%s.%s',
            $user->getId(),
            $type,
            uniqid(),
            $file->guessExtension()
        );
        $document->setFilePath($fileName);

        $this->entityManager->persist($document);
        $this->entityManager->flush();

        // Notifier l'admin
        $this->notificationService->notifyAdminNewDocument($document);

        $this->logger->info('Document created', [
            'document_id' => $document->getId(),
            'user_id' => $user->getId(),
            'type' => $type
        ]);

        return $document;
    }

    /**
     * Valider un document (action admin)
     */
    public function validateDocument(Document $document, User $validator, string $notes = null): void
    {
        if ($document->getStatus() !== 'pending') {
            throw new \LogicException('Ce document n\'est pas en attente de validation');
        }

        $document->setStatus('validated');
        $document->setValidatedAt(new \DateTimeImmutable());
        $document->setValidatedBy($validator);
        $document->setValidationNotes($notes);

        $this->entityManager->persist($document);
        $this->entityManager->flush();

        // Mettre à jour le score de confiance
        $this->trustScoreCalculator->updateUserTrustScore($document->getUser());

        // Notifier l'utilisateur
        $this->notificationService->notifyDocumentValidation($document, true);

        $this->logger->info('Document validated', [
            'document_id' => $document->getId(),
            'validator_id' => $validator->getId()
        ]);
    }

    /**
     * Rejeter un document (action admin)
     */
    public function rejectDocument(
        Document $document,
        User $validator,
        string $reason,
        string $notes = null
    ): void {
        if ($document->getStatus() !== 'pending') {
            throw new \LogicException('Ce document n\'est pas en attente de validation');
        }

        $document->setStatus('rejected');
        $document->setValidatedAt(new \DateTimeImmutable());
        $document->setValidatedBy($validator);
        $document->setRejectionReason($reason);
        $document->setValidationNotes($notes);

        $this->entityManager->persist($document);
        $this->entityManager->flush();

        // Notifier l'utilisateur
        $this->notificationService->notifyDocumentValidation($document, false);

        $this->logger->info('Document rejected', [
            'document_id' => $document->getId(),
            'validator_id' => $validator->getId(),
            'reason' => $reason
        ]);
    }

    /**
     * Vérifier si un utilisateur a tous les documents requis
     */
    public function checkRequiredDocuments(User $user): array
    {
        $required = [
            'identity' => ['id_card' => false, 'selfie' => false],
            'financial' => false
        ];

        $documents = $user->getDocuments();

        foreach ($documents as $document) {
            if ($document->getStatus() !== 'validated') {
                continue;
            }

            switch ($document->getType()) {
                case 'identity':
                    if ($document->getSubType() === 'id_card') {
                        $required['identity']['id_card'] = true;
                    } elseif ($document->getSubType() === 'selfie') {
                        $required['identity']['selfie'] = true;
                    }
                    break;

                case 'financial':
                    $required['financial'] = true;
                    break;
            }
        }

        return [
            'identity_complete' => $required['identity']['id_card'] && $required['identity']['selfie'],
            'financial_complete' => $required['financial'],
            'all_complete' => $required['identity']['id_card'] &&
                $required['identity']['selfie'] &&
                $required['financial'],
            'details' => $required
        ];
    }

    /**
     * Obtenir les documents en attente de validation
     */
    public function getPendingDocuments(int $limit = null): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('d')
            ->from(Document::class, 'd')
            ->where('d.status = :status')
            ->setParameter('status', 'pending')
            ->orderBy('d.createdAt', 'ASC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Obtenir les statistiques de validation
     */
    public function getValidationStats(): array
    {
        $stats = $this->entityManager->createQueryBuilder()
            ->select('d.status, COUNT(d.id) as count')
            ->from(Document::class, 'd')
            ->groupBy('d.status')
            ->getQuery()
            ->getResult();

        $result = [
            'pending' => 0,
            'validated' => 0,
            'rejected' => 0,
            'total' => 0
        ];

        foreach ($stats as $stat) {
            $result[$stat['status']] = $stat['count'];
            $result['total'] += $stat['count'];
        }

        // Stats par type
        $typeStats = $this->entityManager->createQueryBuilder()
            ->select('d.type, d.status, COUNT(d.id) as count')
            ->from(Document::class, 'd')
            ->groupBy('d.type, d.status')
            ->getQuery()
            ->getResult();

        $result['by_type'] = [];
        foreach ($typeStats as $stat) {
            if (!isset($result['by_type'][$stat['type']])) {
                $result['by_type'][$stat['type']] = [
                    'pending' => 0,
                    'validated' => 0,
                    'rejected' => 0
                ];
            }
            $result['by_type'][$stat['type']][$stat['status']] = $stat['count'];
        }

        return $result;
    }

    /**
     * Supprimer un document
     */
    public function deleteDocument(Document $document): void
    {
        // Supprimer le fichier physique
        $filePath = $document->getFullFilePath();
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $user = $document->getUser();

        $this->entityManager->remove($document);
        $this->entityManager->flush();

        // Recalculer le score de confiance
        $this->trustScoreCalculator->updateUserTrustScore($user);

        $this->logger->info('Document deleted', [
            'document_id' => $document->getId()
        ]);
    }
}
