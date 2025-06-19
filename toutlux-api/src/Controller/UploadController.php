<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Psr\Log\LoggerInterface;

#[IsGranted('ROLE_USER')]
class UploadController extends AbstractController
{
    public function __construct(
        private SluggerInterface $slugger,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/api/upload', name: 'api_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        try {
            /** @var UploadedFile|null $file */
            $file = $request->files->get('file');
            $type = $request->request->get('type', 'general');

            if (!$file || !$file->isValid()) {
                return new JsonResponse([
                    'error' => 'No valid file provided',
                    'message' => $file?->getErrorMessage() ?? 'File is missing or invalid'
                ], 400);
            }

            // Validate file size and MIME type before moving the file
            $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
            if (!in_array($file->getMimeType(), $allowedMimes)) {
                return new JsonResponse([
                    'error' => 'Invalid file type. Only JPEG, PNG, and PDF are allowed.'
                ], 400);
            }

            $fileSize = $file->getSize(); // Get the size before moving the file
            if ($fileSize > 10 * 1024 * 1024) {
                return new JsonResponse([
                    'error' => 'File too large. Maximum size is 10MB.'
                ], 400);
            }

            // Generate a safe filename
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugger->slug($originalFilename);
            $extension = $file->guessExtension() ?: 'jpg';
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;

            // Determine the upload directory
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/';
            $typeDir = match ($type) {
                'profile' => 'profiles/',
                'identity_card' => 'identity/',
                'selfieWithId' => 'selfies/',
                'incomeProof' => 'income/',
                'ownershipProof' => 'ownership/',
                default => 'general/'
            };

            $fullUploadDir = $uploadDir . $typeDir;

            // Ensure the directory exists
            if (!is_dir($fullUploadDir)) {
                mkdir($fullUploadDir, 0755, true);
            }

            // Move the file to the target directory
            $file->move($fullUploadDir, $newFilename);

            // Generate the public URL
            $publicUrl = '/uploads/' . $typeDir . $newFilename;

            return new JsonResponse([
                'success' => true,
                'url' => $publicUrl,
                'filename' => $newFilename,
                'type' => $type,
                'size' => $fileSize // Use the size retrieved before moving the file
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Upload failed: ' . $e->getMessage());

            return new JsonResponse([
                'error' => 'Upload failed',
                'message' => 'An error occurred during file upload',
                'debug' => $_ENV['APP_ENV'] === 'dev' ? $e->getMessage() : null
            ], 500);
        }
    }
}
