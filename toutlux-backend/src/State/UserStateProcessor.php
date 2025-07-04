<?php

namespace App\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\Service\Auth\EmailVerificationService;
use App\Service\Document\TrustScoreCalculator;
use App\Service\Email\WelcomeEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * State Processor pour les opérations d'écriture sur User
 * Architecture CQRS avec API Platform 4.1
 */
final class UserStateProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $persistProcessor,
        private ProcessorInterface $removeProcessor,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private WelcomeEmailService $welcomeEmailService,
        private EmailVerificationService $emailVerificationService,
        private TrustScoreCalculator $trustScoreCalculator
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof User) {
            throw new BadRequestHttpException('Expected User entity');
        }

        // Pour une suppression
        if ($operation instanceof DeleteOperationInterface) {
            return $this->removeProcessor->process($data, $operation, $uriVariables, $context);
        }

        // Pour une création
        if (!$data->getId()) {
            // Hasher le mot de passe si fourni
            if ($plainPassword = $data->getPlainPassword()) {
                $data->setPassword($this->passwordHasher->hashPassword($data, $plainPassword));
                $data->eraseCredentials();
            }

            // Définir les rôles par défaut
            if (empty($data->getRoles())) {
                $data->setRoles(['ROLE_USER']);
            }

            // Persister
            $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

            // Envoyer les emails de bienvenue
            $this->welcomeEmailService->sendWelcomeEmail($result);

            // Si pas Google, envoyer email de vérification
            if (!$result->getGoogleId()) {
                $this->emailVerificationService->sendVerificationEmail($result);
            }

            return $result;
        }

        // Pour une mise à jour
        // Vérifier le profil et calculer le trust score
        $data->checkProfileCompletion();
        $this->trustScoreCalculator->updateUserTrustScore($data);

        // Si changement de mot de passe
        if ($plainPassword = $data->getPlainPassword()) {
            $data->setPassword($this->passwordHasher->hashPassword($data, $plainPassword));
            $data->eraseCredentials();
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
