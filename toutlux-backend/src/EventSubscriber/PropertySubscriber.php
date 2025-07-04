<?php

namespace App\EventSubscriber;

use App\Entity\Property;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\String\Slugger\SluggerInterface;

class PropertySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private SluggerInterface $slugger
    ) {}

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Property) {
            return;
        }

        // Définir le propriétaire si non défini
        if (!$entity->getOwner() && $this->security->getUser() instanceof User) {
            $entity->setOwner($this->security->getUser());
        }

        // Générer les métadonnées SEO si non définies
        $this->generateSeoMetadata($entity);

        // Vérifier automatiquement si l'utilisateur est vérifié
        if ($entity->getOwner() && $entity->getOwner()->isIdentityVerified()) {
            $entity->setVerified(true);
        }
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Property) {
            return;
        }

        // Mettre à jour les métadonnées SEO
        $this->generateSeoMetadata($entity);
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
