<?php

namespace App\Serializer;

use App\Entity\MediaObject;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * Normalizer pour les MediaObject afin d'ajouter l'URL complète
 */
class MediaObjectNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'MEDIA_OBJECT_NORMALIZER_ALREADY_CALLED';

    public function __construct(
        private StorageInterface $storage,
        private string $baseUrl
    ) {}

    public function normalize($object, ?string $format = null, array $context = []): array
    {
        $context[self::ALREADY_CALLED] = true;

        /** @var MediaObject $object */
        $data = $this->normalizer->normalize($object, $format, $context);

        // Ajouter l'URL complète du fichier
        if ($object->getFilePath()) {
            $data['contentUrl'] = $this->baseUrl . $this->storage->resolveUri($object, 'file');
        }

        // Ajouter des métadonnées utiles
        if ($object->getSize()) {
            $data['formattedSize'] = $this->formatBytes($object->getSize());
        }

        // Si c'est une image, ajouter un thumbnail URL si disponible
        if ($object->getMimeType() && str_starts_with($object->getMimeType(), 'image/')) {
            $data['thumbnailUrl'] = $data['contentUrl']; // Pourrait être un vrai thumbnail
            $data['isImage'] = true;
        } else {
            $data['isImage'] = false;
        }

        return $data;
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        // Éviter la récursion infinie
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof MediaObject;
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
