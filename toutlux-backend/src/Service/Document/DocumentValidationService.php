<?php

namespace App\Service\Document;

use App\Entity\Document;
use App\Entity\User;
use App\Enum\DocumentStatus;
use App\Enum\DocumentType;
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

        // Convertir le string en DocumentType enum
        $documentType = $this->getDocumentTypeFromString($type, $subType);
        $document->setType($documentType);
        $document->setSubType($subType);

        $document->setFileName($file->getClientOriginalName());
        $document->setFileSize($file->getSize());
        $document->setMimeType($file->getMimeType());
        $document->setStatus(DocumentStatus::PENDING);

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
        if ($document->getStatus() !== DocumentStatus::PENDING) {
            throw new \LogicException('Ce document n\'est pas en attente de validation');
        }

        $document->approve($validator, $notes);

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
        if ($document->getStatus() !== DocumentStatus::PENDING) {
            throw new \LogicException('Ce document n\'est pas en attente de validation');
        }

        $document->reject($validator, $reason, $notes);

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
     * Convertir un string en DocumentType enum
     */
    private function getDocumentTypeFromString(string $type, ?string $subType): DocumentType
    {
        // Map des types string vers les enums
        $typeMap = [
            'identity' => match($subType) {
                'id_card', 'recto', 'verso' => DocumentType::IDENTITY_CARD,
                'passport' => DocumentType::PASSPORT,
                'driver_license' => DocumentType::DRIVER_LICENSE,
                'selfie' => DocumentType::SELFIE_WITH_ID,
                default => DocumentType::IDENTITY_CARD
            },
            'financial' => match($subType) {
                'bank_statement' => DocumentType::BANK_STATEMENT,
                'payslip' => DocumentType::PAYSLIP,
                'tax_return' => DocumentType::TAX_RETURN,
                'employment_contract' => DocumentType::EMPLOYMENT_CONTRACT,
                default => DocumentType::PROOF_OF_INCOME
            }
        ];

        return $typeMap[$type] ?? DocumentType::IDENTITY_CARD;
    }

    /**
     * Vérifier si un utilisateur a tous les documents requis
     */
    public function checkRequiredDocuments(User $user): array
    {
        $required = [
            'identity' => ['id_document' => false, 'selfie' => false],
            'financial' => false
        ];

        $documents = $user->getDocuments();

        foreach ($documents as $document) {
            if ($document->getStatus() !== DocumentStatus::APPROVED) {
                continue;
            }

            if ($document->getType()->isIdentityDocument()) {
                if (in_array($document->getType(), [
                    DocumentType::IDENTITY_CARD,
                    DocumentType::PASSPORT,
                    DocumentType::DRIVER_LICENSE
                ])) {
                    $required['identity']['id_document'] = true;
                } elseif ($document->getType() === DocumentType::SELFIE_WITH_ID) {
                    $required['identity']['selfie'] = true;
                }
            } elseif ($document->getType()->isFinancialDocument()) {
                $required['financial'] = true;
            }
        }

        return [
            'identity_complete' => $required['identity']['id_document'] && $required['identity']['selfie'],
            'financial_complete' => $required['financial'],
            'all_complete' => $required['identity']['id_document'] &&
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
        return $this->entityManager->getRepository(Document::class)
            ->findByStatus(DocumentStatus::PENDING, $limit);
    }

    /**
     * Obtenir les statistiques de validation
     */
    public function getValidationStats(): array
    {
        return $this->entityManager->getRepository(Document::class)
            ->getStatsByTypeAndStatus();
    }

    /**
     * Supprimer un document
     */
    public function deleteDocument(Document $document): void
    {
        if (!$document->canBeDeleted()) {
            throw new \LogicException('Ce document ne peut pas être supprimé');
        }

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
