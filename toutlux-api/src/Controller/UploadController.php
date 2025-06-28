<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Psr\Log\LoggerInterface;

#[IsGranted('ROLE_USER')]
class UploadController extends AbstractController
{
    public function __construct(
        private SluggerInterface $slugger,
        private LoggerInterface $logger,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir
    ) {
    }

    #[Route('/api/upload', name: 'api_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        try {
            /** @var UploadedFile|null $file */
            $file = $request->files->get('file');
            $type = $request->request->get('type', 'general');

            // ✅ CORRECTION: Validation d'entrée plus robuste
            if (!$file) {
                return new JsonResponse([
                    'error' => 'No file provided',
                    'message' => 'Please select a file to upload'
                ], 400);
            }

            if (!$file->isValid()) {
                return new JsonResponse([
                    'error' => 'Invalid file',
                    'message' => $file->getErrorMessage()
                ], 400);
            }

            // ✅ CORRECTION: Validation MIME type et taille avant déplacement
            $allowedMimes = [
                'image/jpeg',
                'image/png',
                'image/jpg',
                'application/pdf'
            ];

            $detectedMime = $file->getMimeType();
            if (!in_array($detectedMime, $allowedMimes)) {
                return new JsonResponse([
                    'error' => 'Invalid file type',
                    'message' => 'Only JPEG, PNG, and PDF files are allowed',
                    'detected_type' => $detectedMime
                ], 400);
            }

            $fileSize = $file->getSize();
            $maxSize = 10 * 1024 * 1024; // 10MB
            if ($fileSize > $maxSize) {
                return new JsonResponse([
                    'error' => 'File too large',
                    'message' => 'Maximum file size is 10MB',
                    'file_size' => $fileSize,
                    'max_size' => $maxSize
                ], 400);
            }

            // ✅ CORRECTION: Génération de nom de fichier sécurisée
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugger->slug($originalFilename);
            $extension = $file->guessExtension() ?: 'jpg';
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;

            // ✅ CORRECTION: Détermination du répertoire avec validation
            $allowedTypes = [
                'profile' => 'profiles/',
                'identity_card' => 'identity/',
                'selfieWithId' => 'selfies/',
                'incomeProof' => 'income/',
                'ownershipProof' => 'ownership/',
                'general' => 'general/'
            ];

            $typeDir = $allowedTypes[$type] ?? 'general/';
            $uploadDir = $this->projectDir . '/public/uploads/';
            $fullUploadDir = $uploadDir . $typeDir;

            // ✅ CORRECTION: Création de répertoire avec gestion d'erreurs
            if (!is_dir($fullUploadDir)) {
                if (!mkdir($fullUploadDir, 0755, true)) {
                    throw new \RuntimeException('Failed to create upload directory: ' . $fullUploadDir);
                }
            }

            // ✅ CORRECTION: Vérification des permissions d'écriture
            if (!is_writable($fullUploadDir)) {
                throw new \RuntimeException('Upload directory is not writable: ' . $fullUploadDir);
            }

            // Déplacer le fichier
            $file->move($fullUploadDir, $newFilename);

            // Vérifier que le fichier a bien été déplacé
            $finalPath = $fullUploadDir . $newFilename;
            if (!file_exists($finalPath)) {
                throw new \RuntimeException('File was not properly moved to destination');
            }

            // Générer l'URL publique
            $publicUrl = '/uploads/' . $typeDir . $newFilename;

            $this->logger->info('File uploaded successfully', [
                'user_id' => $this->getUser()?->getId(),
                'original_filename' => $file->getClientOriginalName(),
                'new_filename' => $newFilename,
                'type' => $type,
                'size' => $fileSize,
                'mime_type' => $detectedMime,
                'public_url' => $publicUrl
            ]);

            return new JsonResponse([
                'success' => true,
                'url' => $publicUrl,
                'filename' => $newFilename,
                'type' => $type,
                'size' => $fileSize,
                'mime_type' => $detectedMime
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Upload failed', [
                'user_id' => $this->getUser()?->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'error' => 'Upload failed',
                'message' => 'An error occurred during file upload',
                'debug' => $_ENV['APP_ENV'] === 'dev' ? $e->getMessage() : null
            ], 500);
        }
    }
}
