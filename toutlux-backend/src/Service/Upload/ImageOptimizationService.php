<?php

namespace App\Service\Upload;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;
use Psr\Log\LoggerInterface;

class ImageOptimizationService
{
    private const IMAGE_SIZES = [
        'avatar' => [
            'thumb' => ['width' => 50, 'height' => 50],
            'small' => ['width' => 150, 'height' => 150],
            'medium' => ['width' => 300, 'height' => 300]
        ],
        'property' => [
            'thumb' => ['width' => 150, 'height' => 100],
            'small' => ['width' => 400, 'height' => 300],
            'medium' => ['width' => 800, 'height' => 600],
            'large' => ['width' => 1200, 'height' => 900]
        ],
        'identity' => [
            'medium' => ['width' => 800, 'height' => 600]
        ],
        'financial' => [
            'medium' => ['width' => 800, 'height' => 600]
        ]
    ];

    private const JPEG_QUALITY = 85;
    private const PNG_COMPRESSION = 9;
    private const WEBP_QUALITY = 80;

    private Imagine $imagine;

    public function __construct(
        private LoggerInterface $logger
    ) {
        $this->imagine = new Imagine();
    }

    /**
     * Optimiser une image
     */
    public function optimize(string $sourcePath, string $type): array
    {
        try {
            if (!file_exists($sourcePath)) {
                throw new \InvalidArgumentException('Fichier source introuvable');
            }

            $image = $this->imagine->open($sourcePath);
            $originalSize = filesize($sourcePath);

            // Obtenir les dimensions originales
            $originalDimensions = $image->getSize();

            // Optimiser l'image principale
            $optimizedPath = $this->optimizeMainImage($image, $sourcePath, $type);

            // Créer les variations
            $variations = $this->createVariations($sourcePath, $type);

            $this->logger->info('Image optimized successfully', [
                'original_size' => $originalSize,
                'optimized_size' => filesize($optimizedPath),
                'reduction' => round((1 - filesize($optimizedPath) / $originalSize) * 100, 2) . '%',
                'variations' => count($variations)
            ]);

            return [
                'success' => true,
                'path' => $optimizedPath,
                'original_size' => $originalSize,
                'optimized_size' => filesize($optimizedPath),
                'variations' => $variations,
                'dimensions' => [
                    'width' => $originalDimensions->getWidth(),
                    'height' => $originalDimensions->getHeight()
                ]
            ];

        } catch (\Exception $e) {
            $this->logger->error('Image optimization failed', [
                'path' => $sourcePath,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'path' => $sourcePath
            ];
        }
    }

    /**
     * Optimiser l'image principale
     */
    private function optimizeMainImage(ImageInterface $image, string $sourcePath, string $type): string
    {
        $size = $image->getSize();
        $maxWidth = $type === 'avatar' ? 500 : 1920;
        $maxHeight = $type === 'avatar' ? 500 : 1080;

        // Redimensionner si nécessaire
        if ($size->getWidth() > $maxWidth || $size->getHeight() > $maxHeight) {
            $image = $image->thumbnail(
                new Box($maxWidth, $maxHeight),
                ImageInterface::THUMBNAIL_INSET
            );
        }

        // Déterminer le format de sortie
        $extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
        $options = $this->getOptimizationOptions($extension);

        // Sauvegarder l'image optimisée
        $image->save($sourcePath, $options);

        return $sourcePath;
    }

    /**
     * Créer les variations d'une image
     */
    private function createVariations(string $sourcePath, string $type): array
    {
        if (!isset(self::IMAGE_SIZES[$type])) {
            return [];
        }

        $variations = [];
        $pathInfo = pathinfo($sourcePath);
        $directory = $pathInfo['dirname'];
        $filename = $pathInfo['filename'];
        $extension = $pathInfo['extension'];

        $sourceImage = $this->imagine->open($sourcePath);

        foreach (self::IMAGE_SIZES[$type] as $sizeName => $dimensions) {
            try {
                $variationPath = sprintf(
                    '%s/%s_%s.%s',
                    $directory,
                    $filename,
                    $sizeName,
                    $extension
                );

                // Créer la variation
                $variation = $this->createThumbnail(
                    $sourceImage,
                    $dimensions['width'],
                    $dimensions['height'],
                    $type === 'avatar' // crop pour avatars
                );

                // Sauvegarder avec les options d'optimisation
                $options = $this->getOptimizationOptions($extension);
                $variation->save($variationPath, $options);

                $variations[$sizeName] = [
                    'path' => $variationPath,
                    'width' => $dimensions['width'],
                    'height' => $dimensions['height'],
                    'size' => filesize($variationPath)
                ];

            } catch (\Exception $e) {
                $this->logger->warning('Failed to create image variation', [
                    'size' => $sizeName,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $variations;
    }

    /**
     * Créer une miniature
     */
    private function createThumbnail(
        ImageInterface $image,
        int $width,
        int $height,
        bool $crop = false
    ): ImageInterface {
        if ($crop) {
            // Crop centré pour les avatars
            return $this->cropCenter($image, $width, $height);
        } else {
            // Redimensionnement proportionnel
            return $image->thumbnail(
                new Box($width, $height),
                ImageInterface::THUMBNAIL_INSET
            );
        }
    }

    /**
     * Crop centré
     */
    private function cropCenter(ImageInterface $image, int $width, int $height): ImageInterface
    {
        $size = $image->getSize();
        $ratioWidth = $width / $size->getWidth();
        $ratioHeight = $height / $size->getHeight();
        $ratio = max($ratioWidth, $ratioHeight);

        // Redimensionner pour que l'image couvre entièrement la zone
        $newWidth = (int) ($size->getWidth() * $ratio);
        $newHeight = (int) ($size->getHeight() * $ratio);

        $resized = $image->resize(new Box($newWidth, $newHeight));

        // Calculer le point de départ pour le crop centré
        $startX = (int) (($newWidth - $width) / 2);
        $startY = (int) (($newHeight - $height) / 2);

        return $resized->crop(new Point($startX, $startY), new Box($width, $height));
    }

    /**
     * Obtenir les options d'optimisation selon le format
     */
    private function getOptimizationOptions(string $extension): array
    {
        switch (strtolower($extension)) {
            case 'jpg':
            case 'jpeg':
                return [
                    'jpeg_quality' => self::JPEG_QUALITY,
                    'format' => 'jpg'
                ];

            case 'png':
                return [
                    'png_compression_level' => self::PNG_COMPRESSION,
                    'format' => 'png'
                ];

            case 'webp':
                return [
                    'webp_quality' => self::WEBP_QUALITY,
                    'format' => 'webp'
                ];

            default:
                return ['format' => $extension];
        }
    }

    /**
     * Convertir une image vers un autre format
     */
    public function convert(string $sourcePath, string $targetFormat): array
    {
        try {
            $image = $this->imagine->open($sourcePath);
            $pathInfo = pathinfo($sourcePath);

            $targetPath = sprintf(
                '%s/%s.%s',
                $pathInfo['dirname'],
                $pathInfo['filename'],
                $targetFormat
            );

            $options = $this->getOptimizationOptions($targetFormat);
            $image->save($targetPath, $options);

            return [
                'success' => true,
                'source_path' => $sourcePath,
                'target_path' => $targetPath,
                'source_size' => filesize($sourcePath),
                'target_size' => filesize($targetPath)
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtenir les métadonnées EXIF
     */
    public function getExifData(string $imagePath): array
    {
        if (!function_exists('exif_read_data')) {
            return [];
        }

        try {
            $exif = @exif_read_data($imagePath);
            if (!$exif) {
                return [];
            }

            // Extraire les données importantes
            return [
                'make' => $exif['Make'] ?? null,
                'model' => $exif['Model'] ?? null,
                'datetime' => $exif['DateTime'] ?? null,
                'orientation' => $exif['Orientation'] ?? null,
                'gps' => $this->extractGpsData($exif),
                'exposure_time' => $exif['ExposureTime'] ?? null,
                'f_number' => $exif['FNumber'] ?? null,
                'iso' => $exif['ISOSpeedRatings'] ?? null
            ];

        } catch (\Exception $e) {
            $this->logger->warning('Failed to read EXIF data', [
                'path' => $imagePath,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Extraire les données GPS
     */
    private function extractGpsData(array $exif): ?array
    {
        if (!isset($exif['GPSLatitude']) || !isset($exif['GPSLongitude'])) {
            return null;
        }

        try {
            $lat = $this->convertGpsCoordinate(
                $exif['GPSLatitude'],
                $exif['GPSLatitudeRef'] ?? 'N'
            );

            $lng = $this->convertGpsCoordinate(
                $exif['GPSLongitude'],
                $exif['GPSLongitudeRef'] ?? 'E'
            );

            return [
                'latitude' => $lat,
                'longitude' => $lng
            ];

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Convertir les coordonnées GPS
     */
    private function convertGpsCoordinate(array $coordinate, string $ref): float
    {
        $degrees = $this->evaluateFraction($coordinate[0]);
        $minutes = $this->evaluateFraction($coordinate[1]);
        $seconds = $this->evaluateFraction($coordinate[2]);

        $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);

        if ($ref === 'S' || $ref === 'W') {
            $decimal = -$decimal;
        }

        return $decimal;
    }

    /**
     * Évaluer une fraction
     */
    private function evaluateFraction(string $fraction): float
    {
        $parts = explode('/', $fraction);
        if (count($parts) === 2 && $parts[1] != 0) {
            return $parts[0] / $parts[1];
        }
        return (float) $fraction;
    }

    /**
     * Supprimer les métadonnées EXIF
     */
    public function stripExifData(string $imagePath): bool
    {
        try {
            $image = $this->imagine->open($imagePath);
            $image->save($imagePath);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to strip EXIF data', [
                'path' => $imagePath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
