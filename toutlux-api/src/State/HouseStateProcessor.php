<?php

// src/State/HouseStateProcessor.php
namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\House;
use App\Service\CurrencyService;
use Doctrine\ORM\EntityManagerInterface;

class HouseStateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CurrencyService $currencyService
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof House) {
            return $data;
        }

        // Normalisation de la devise avant sauvegarde
        if ($data->getCurrency()) {
            $normalizedCurrency = $this->currencyService->normalizeCurrency($data->getCurrency());
            $data->setCurrency($normalizedCurrency);
        }

        // Sauvegarde
        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $data;
    }
}
