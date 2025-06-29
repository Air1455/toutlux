<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\House;
use App\Entity\User;
use App\Service\CurrencyService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class HouseStateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CurrencyService $currencyService,
        private Security $security
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof House) {
            return $data;
        }

        // Pour les nouvelles créations, associer l'utilisateur connecté
        if ($operation instanceof \ApiPlatform\Metadata\Post) {
            /** @var User $currentUser */
            $currentUser = $this->security->getUser();

            if (!$currentUser instanceof User) {
                throw new \LogicException('User must be authenticated to create a house');
            }

            // Vérifier que l'utilisateur peut créer des annonces
            if (!$currentUser->isListingCreationAllowed()) {
                throw new \LogicException('Your profile must be verified to create listings');
            }

            $data->setUser($currentUser);
        }

        // Normalisation de la devise
        if ($data->getCurrency()) {
            $normalizedCurrency = $this->currencyService->normalizeCurrency($data->getCurrency());
            $data->setCurrency($normalizedCurrency);
        }

        // Si pas de devise, utiliser celle par défaut selon le pays
        if (!$data->getCurrency() && $data->getCountry()) {
            $defaultCurrency = $this->currencyService->getDefaultCurrencyByCountry($data->getCountry());
            $data->setCurrency($defaultCurrency);
        }

        // Validation des coordonnées GPS si fournies
        $location = $data->getLocation();
        if (!empty($location) && (!isset($location['lat']) || !isset($location['lng']))) {
            throw new \InvalidArgumentException('Location must contain lat and lng');
        }

        // Mise à jour des timestamps est gérée par les callbacks Doctrine

        // Sauvegarde
        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $data;
    }
}
