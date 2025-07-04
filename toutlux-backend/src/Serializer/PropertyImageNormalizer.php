<?php

namespace App\Serializer;

use App\Entity\PropertyImage;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * Normalizer pour les PropertyImage afin d'ajouter les URLs complètes
 */
class PropertyImageNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'PROPERTY_IMAGE_NORMALIZER_ALREADY_CALLED';

    public function __construct(
        private StorageInterface $storage,
        private string $baseUrl
    ) {}

    public function normalize($object, ?string $format = null, array $context = []): array
    {
        $context[self::ALREADY_CALLED] = true;

        /** @var PropertyImage $object */
        $data = $this->normalizer->normalize($object, $format, $context);

        // Ajouter l'URL complète de l'image
        if ($object->getImageName()) {
            $data['imageUrl'] = $this->baseUrl . $this->storage->resolveUri($object, 'imageFile');

            // Ajouter des variantes d'URL pour différentes tailles
            // Ces URLs pourraient pointer vers un service de redimensionnement d'images
            $baseImageUrl = $data['imageUrl'];
            $data['thumbnailUrl'] = $this->generateSizeUrl($baseImageUrl, 'thumb');
            $data['smallUrl'] = $this->generateSizeUrl($baseImageUrl, 'small');
            $data['mediumUrl'] = $this->generateSizeUrl($baseImageUrl, 'medium');
            $data['largeUrl'] = $this->generateSizeUrl($baseImageUrl, 'large');
        }

        // Ajouter des informations supplémentaires
        if ($object->getImageSize()) {
            $data['formattedSize'] = $this->formatBytes($object->getImageSize());
        }

        return $data;
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        // Éviter la récursion infinie
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof PropertyImage;
    }

    private function generateSizeUrl(string $baseUrl, string $size): string
    {
        // Simple implémentation - pourrait être remplacé par un vrai service de redimensionnement
        // Ex: utiliser Liip Imagine Bundle
        $parts = pathinfo($baseUrl);
        return sprintf(
            '%s/%s_%s.%s',
            $parts['dirname'],
            $parts['filename'],
            $size,
            $parts['extension'] ?? 'jpg'
        );
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
