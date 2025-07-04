<?php

namespace App\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Property;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * State Processor pour les opérations d'écriture sur Property
 */
final class PropertyStateProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $persistProcessor,
        private ProcessorInterface $removeProcessor,
        private Security $security,
        private SluggerInterface $slugger
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof Property) {
            throw new BadRequestHttpException('Expected Property entity');
        }

        // Pour une suppression
        if ($operation instanceof DeleteOperationInterface) {
            return $this->removeProcessor->process($data, $operation, $uriVariables, $context);
        }

        // Pour une création
        if (!$data->getId()) {
            // Définir le propriétaire
            $data->setOwner($this->security->getUser());

            // Si l'utilisateur est vérifié, marquer la propriété comme vérifiée
            if ($data->getOwner()->isIdentityVerified()) {
                $data->setVerified(true);
            }
        }

        // Générer les métadonnées SEO
        $this->generateSeoMetadata($data);

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }

    private function generateSeoMetadata(Property $property): void
    {
        // Générer le meta title si non défini
        if (empty($property->getMetaTitle())) {
            $type = $property->getType() === 'sale' ? 'Vente' : 'Location';
            $metaTitle = sprintf(
                '%s - %s %s pièces %sm² à %s | TOUTLUX',
                $property->getTitle(),
                $type,
                $property->getRooms(),
                $property->getSurface(),
                $property->getCity()
            );
            $property->setMetaTitle(substr($metaTitle, 0, 60));
        }

        // Générer la meta description si non définie
        if (empty($property->getMetaDescription())) {
            $type = $property->getType() === 'sale' ? 'à vendre' : 'à louer';
            $price = $property->getType() === 'sale'
                ? number_format((float)$property->getPrice(), 0, ',', ' ') . ' €'
                : number_format((float)$property->getPrice(), 0, ',', ' ') . ' €/mois';

            $metaDescription = sprintf(
                '%s %s à %s. %s pièces, %sm², %s chambres. Prix: %s. %s',
                ucfirst($property->getType() === 'sale' ? 'Maison/Appartement' : 'Location'),
                $type,
                $property->getCity(),
                $property->getRooms(),
                $property->getSurface(),
                $property->getBedrooms(),
                $price,
                substr($property->getDescription(), 0, 100) . '...'
            );
            $property->setMetaDescription(substr($metaDescription, 0, 160));
        }
    }
}
