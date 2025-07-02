<?php

namespace App\Service\Upload;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Psr\Log\LoggerInterface;

class FileUploadService
{
    private const UPLOAD_DIRECTORIES = [
        'avatar' => 'avatars',
        'property' => 'properties',
        'identity' => 'documents/identity',
        'financial' => 'documents/financial'
    ];

    private const MAX_FILE_SIZES = [
        'avatar' => 5 * 1024 * 1024,      // 5MB
        'property' => 10 * 1024 * 1024,   // 10MB
        'identity' => 10 * 1024 * 1024,   // 10MB
        'financial' => 10 * 1024 * 1024   // 10MB
    ];

    private const ALLOWED_EXTENSIONS = [
        'avatar' => ['jpg', 'jpeg', 'png', 'webp'],
        'property' => ['jpg', 'jpeg', 'png', 'webp'],
        'identity' => ['jpg', 'jpeg', 'png', 'pdf'],
        'financial' => ['jpg', 'jpeg', 'png', 'pdf']
    ];

    public function __construct(
        private string $uploadsDirectory,
        private SluggerInterface $slugger,
        private ImageOptimizationService $imageOptimizer,
        private LoggerInterface $logger
    ) {}

    /**
     * Upload un fichier
     */
    public function upload(UploadedFile $file, string $type, ?string $userId = null): array
    {
        // Valider le type
        if (!isset(self::UPLOAD_DIRECTORIES[$type])) {
            throw new \InvalidArgumentException(sprintf('Type de fichier invalide : %s', $type));
        }

        // Valider la taille
        if ($file->getSize() > self::MAX_FILE_SIZES[$type]) {
            throw new FileException(sprintf(
                'Le fichier est trop volumineux. Taille maximum : %d MB',
                self::MAX_FILE_SIZES[$type] / 1024 / 1024
            ));
        }

        // Valider l'extension
        $extension = $file->guessExtension();
        if (!in_array($extension, self::ALLOWED_EXTENSIONS[$type])) {
            throw new FileException(sprintf(
                'Type de fichier non autorisé. Extensions acceptées : %s',
                implode(', ', self::ALLOWED_EXTENSIONS[$type])
            ));
        }

        // Générer un nom unique
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $this->generateFilename($safeFilename, $extension, $userId);

        // Déterminer le répertoire de destination
        $targetDirectory = $this->getTargetDirectory($type);
        $this->ensureDirectoryExists($targetDirectory);

        try {
            // Déplacer le fichier
            $file->move($targetDirectory, $newFilename);
            $filePath = $targetDirectory . '/' . $newFilename;

            // Optimiser l'image si c'est une image
            if ($this->isImage($extension)) {
                $optimizationResult = $this->imageOptimizer->optimize($filePath, $type);
                if ($optimizationResult['success']) {
                    $filePath = $optimizationResult['path'];
                    $newFilename = basename($filePath);
                }
            }

            // Calculer le chemin relatif
            $relativePath = $this->getRelativePath($filePath);

            $this->logger->info('File uploaded successfully', [
                'type' => $type,
                'filename' => $newFilename,
                'size' => filesize($filePath)
            ]);

            return [
                'success' => true,
                'filename' => $newFilename,
                'path' => $relativePath,
                'full_path' => $filePath,
                'size' => filesize($filePath),
                'mime_type' => mime_content_type($filePath),
                'url' => $this->getPublicUrl($relativePath)
            ];

        } catch (FileException $e) {
            $this->logger->error('File upload failed', [
                'error' => $e->getMessage(),
                'type' => $type
            ]);

            throw new FileException('Erreur lors de l\'upload du fichier : ' . $e->getMessage());
        }
    }

    /**
     * Upload multiple files
     */
    public function uploadMultiple(array $files, string $type, ?string $userId = null): array
    {
        $results = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                try {
                    $results[] = $this->upload($file, $type, $userId);
                } catch (\Exception $e) {
                    $results[] = [
                        'success' => false,
                        'filename' => $file->getClientOriginalName(),
                        'error' => $e->getMessage()
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Supprimer un fichier
     */
    public function delete(string $filePath): bool
    {
        $fullPath = $this->uploadsDirectory . '/' . $filePath;

        if (!file_exists($fullPath)) {
            $this->logger->warning('File not found for deletion', ['path' => $filePath]);
            return false;
        }

        try {
            unlink($fullPath);

            // Supprimer aussi les thumbnails s'ils existent
            $this->deleteThumbnails($fullPath);

            $this->logger->info('File deleted successfully', ['path' => $filePath]);
            return true;

        } catch (\Exception $e) {
            $this->logger->error('File deletion failed', [
                'path' => $filePath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Déplacer un fichier
     */
    public function move(string $currentPath, string $newType): array
    {
        $fullCurrentPath = $this->uploadsDirectory . '/' . $currentPath;

        if (!file_exists($fullCurrentPath)) {
            throw new FileException('Fichier introuvable');
        }

        $filename = basename($currentPath);
        $targetDirectory = $this->getTargetDirectory($newType);
        $this->ensureDirectoryExists($targetDirectory);

        $newPath = $targetDirectory . '/' . $filename;

        try {
            rename($fullCurrentPath, $newPath);

            return [
                'success' => true,
                'old_path' => $currentPath,
                'new_path' => $this->getRelativePath($newPath)
            ];

        } catch (\Exception $e) {
            throw new FileException('Erreur lors du déplacement du fichier : ' . $e->getMessage());
        }
    }

    /**
     * Générer un nom de fichier unique
     */
    private function generateFilename(string $safeFilename, string $extension, ?string $userId = null): string
    {
        $prefix = $userId ? $userId . '_' : '';
        $timestamp = (new \DateTime())->format('YmdHis');
        $random = bin2hex(random_bytes(4));

        return sprintf('%s%s_%s_%s.%s', $prefix, $safeFilename, $timestamp, $random, $extension);
    }

    /**
     * Obtenir le répertoire cible
     */
    private function getTargetDirectory(string $type): string
    {
        return $this->uploadsDirectory . '/' . self::UPLOAD_DIRECTORIES[$type];
    }

    /**
     * S'assurer que le répertoire existe
     */
    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0777, true)) {
                throw new FileException('Impossible de créer le répertoire de destination');
            }
        }
    }

    /**
     * Obtenir le chemin relatif
     */
    private function getRelativePath(string $fullPath): string
    {
        return str_replace($this->uploadsDirectory . '/', '', $fullPath);
    }

    /**
     * Obtenir l'URL publique
     */
    private function getPublicUrl(string $relativePath): string
    {
        return '/uploads/' . $relativePath;
    }

    /**
     * Vérifier si c'est une image
     */
    private function isImage(string $extension): bool
    {
        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp']);
    }

    /**
     * Supprimer les thumbnails
     */
    private function deleteThumbnails(string $filePath): void
    {
        $pathInfo = pathinfo($filePath);
        $thumbnailPatterns = [
            $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_thumb.' . $pathInfo['extension'],
            $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_small.' . $pathInfo['extension'],
            $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_medium.' . $pathInfo['extension'],
            $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_large.' . $pathInfo['extension']
        ];

        foreach ($thumbnailPatterns as $pattern) {
            if (file_exists($pattern)) {
                unlink($pattern);
            }
        }
    }

    /**
     * Obtenir les informations d'un fichier
     */
    public function getFileInfo(string $filePath): ?array
    {
        $fullPath = $this->uploadsDirectory . '/' . $filePath;

        if (!file_exists($fullPath)) {
            return null;
        }

        $info = [
            'path' => $filePath,
            'full_path' => $fullPath,
            'url' => $this->getPublicUrl($filePath),
            'size' => filesize($fullPath),
            'mime_type' => mime_content_type($fullPath),
            'extension' => pathinfo($fullPath, PATHINFO_EXTENSION),
            'created_at' => filemtime($fullPath),
            'is_image' => false,
            'dimensions' => null
        ];

        // Si c'est une image, obtenir les dimensions
        if ($this->isImage($info['extension'])) {
            $info['is_image'] = true;
            $dimensions = getimagesize($fullPath);
            if ($dimensions) {
                $info['dimensions'] = [
                    'width' => $dimensions[0],
                    'height' => $dimensions[1]
                ];
            }
        }

        return $info;
    }

    /**
     * Valider un fichier sans l'uploader
     */
    public function validateFile(UploadedFile $file, string $type): array
    {
        $errors = [];

        // Vérifier le type
        if (!isset(self::UPLOAD_DIRECTORIES[$type])) {
            $errors[] = 'Type de fichier invalide';
        }

        // Vérifier la taille
        if ($file->getSize() > self::MAX_FILE_SIZES[$type]) {
            $errors[] = sprintf(
                'Fichier trop volumineux (max: %d MB)',
                self::MAX_FILE_SIZES[$type] / 1024 / 1024
            );
        }

        // Vérifier l'extension
        $extension = $file->guessExtension();
        if (!in_array($extension, self::ALLOWED_EXTENSIONS[$type])) {
            $errors[] = sprintf(
                'Extension non autorisée. Acceptées: %s',
                implode(', ', self::ALLOWED_EXTENSIONS[$type])
            );
        }

        // Vérifier l'intégrité du fichier
        if (!$file->isValid()) {
            $errors[] = 'Le fichier semble corrompu';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Obtenir l'espace disque utilisé
     */
    public function getDiskUsage(?string $userId = null): array
    {
        $totalSize = 0;
        $fileCount = 0;
        $byType = [];

        foreach (self::UPLOAD_DIRECTORIES as $type => $directory) {
            $path = $this->uploadsDirectory . '/' . $directory;
            if (!is_dir($path)) {
                continue;
            }

            $typeSize = 0;
            $typeCount = 0;

            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path)
            );

            foreach ($files as $file) {
                if ($file->isFile()) {
                    $filename = $file->getFilename();

                    // Si userId est spécifié, filtrer par utilisateur
                    if ($userId && !str_starts_with($filename, $userId . '_')) {
                        continue;
                    }

                    $size = $file->getSize();
                    $typeSize += $size;
                    $typeCount++;
                }
            }

            $byType[$type] = [
                'size' => $typeSize,
                'count' => $typeCount,
                'size_formatted' => $this->formatBytes($typeSize)
            ];

            $totalSize += $typeSize;
            $fileCount += $typeCount;
        }

        return [
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize),
            'file_count' => $fileCount,
            'by_type' => $byType,
            'user_id' => $userId
        ];
    }

    /**
     * Nettoyer les vieux fichiers
     */
    public function cleanupOldFiles(int $daysOld = 30): array
    {
        $deletedCount = 0;
        $deletedSize = 0;
        $errors = [];
        $cutoffTime = time() - ($daysOld * 24 * 60 * 60);

        foreach (self::UPLOAD_DIRECTORIES as $type => $directory) {
            $path = $this->uploadsDirectory . '/' . $directory;
            if (!is_dir($path)) {
                continue;
            }

            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path)
            );

            foreach ($files as $file) {
                if ($file->isFile() && $file->getMTime() < $cutoffTime) {
                    try {
                        $size = $file->getSize();
                        unlink($file->getPathname());
                        $deletedCount++;
                        $deletedSize += $size;
                    } catch (\Exception $e) {
                        $errors[] = [
                            'file' => $file->getPathname(),
                            'error' => $e->getMessage()
                        ];
                    }
                }
            }
        }

        return [
            'deleted_count' => $deletedCount,
            'deleted_size' => $deletedSize,
            'deleted_size_formatted' => $this->formatBytes($deletedSize),
            'errors' => $errors
        ];
    }

    /**
     * Formater les bytes
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
