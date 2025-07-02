<?php

namespace App\Service\Email;

use App\Entity\User;
use App\Service\Auth\EmailVerificationService;

class WelcomeEmailService
{
    public function __construct(
        private EmailService $emailService,
        private EmailVerificationService $emailVerificationService,
        private NotificationEmailService $notificationService
    ) {}

    public function sendWelcomeEmail(User $user): void
    {
        $isGoogleUser = !empty($user->getGoogleId());

        // Context pour l'email
        $context = $this->emailService->createEmailContext([
            'user' => $user,
            'firstName' => $user->getFirstName() ?: 'Cher utilisateur',
            'isGoogleUser' => $isGoogleUser,
            'needsVerification' => !$user->isVerified() && !$isGoogleUser,
            'profileCompletionUrl' => $_ENV['APP_URL'] . '/profile',
            'browsePropertiesUrl' => $_ENV['APP_URL'] . '/properties',
        ]);

        // Si l'utilisateur n'est pas Google et n'est pas vérifié, inclure le lien de vérification
        if (!$isGoogleUser && !$user->isVerified()) {
            $context['verificationUrl'] = $this->emailVerificationService->generateVerificationUrl($user);
        }

        // Envoyer l'email de bienvenue
        $this->emailService->sendEmail(
            $user->getEmail(),
            'Bienvenue sur TOUTLUX - Votre agence immobilière en ligne',
            'emails/welcome.html.twig',
            $context
        );

        // Créer une notification in-app
        $this->notificationService->createNotification(
            $user,
            'welcome',
            'Bienvenue sur TOUTLUX !',
            'Nous sommes ravis de vous accueillir. Complétez votre profil pour accéder à toutes les fonctionnalités.',
            [
                'action' => 'complete_profile',
                'action_url' => '/profile'
            ]
        );

        // Si ce n'est pas un utilisateur Google, envoyer aussi l'email de vérification
        if (!$isGoogleUser && !$user->isVerified()) {
            $this->emailVerificationService->sendVerificationEmail($user);
        }
    }

    public function sendWelcomeBackEmail(User $user): void
    {
        $context = $this->emailService->createEmailContext([
            'user' => $user,
            'firstName' => $user->getFirstName() ?: 'Cher utilisateur',
            'lastLoginDate' => $user->getLastLoginAt() ? $user->getLastLoginAt()->format('d/m/Y') : null,
            'newPropertiesCount' => $this->getNewPropertiesCount($user),
            'browsePropertiesUrl' => $_ENV['APP_URL'] . '/properties',
        ]);

        $this->emailService->sendEmail(
            $user->getEmail(),
            'Bon retour sur TOUTLUX !',
            'emails/welcome_back.html.twig',
            $context
        );
    }

    private function getNewPropertiesCount(User $user): int
    {
        // TODO: Implémenter la logique pour compter les nouvelles propriétés
        // depuis la dernière visite de l'utilisateur
        return 0;
    }

    public function sendProfileCompletionReminder(User $user): void
    {
        $missingSteps = $this->getMissingProfileSteps($user);

        if (empty($missingSteps)) {
            return;
        }

        $context = $this->emailService->createEmailContext([
            'user' => $user,
            'firstName' => $user->getFirstName() ?: 'Cher utilisateur',
            'missingSteps' => $missingSteps,
            'trustScore' => $user->getTrustScore(),
            'profileUrl' => $_ENV['APP_URL'] . '/profile',
        ]);

        $this->emailService->sendEmail(
            $user->getEmail(),
            'Complétez votre profil TOUTLUX',
            'emails/profile_completion_reminder.html.twig',
            $context
        );
    }

    private function getMissingProfileSteps(User $user): array
    {
        $steps = [];

        // Informations personnelles
        if (empty($user->getFirstName()) || empty($user->getLastName()) || empty($user->getPhone())) {
            $steps[] = [
                'name' => 'Informations personnelles',
                'description' => 'Nom, prénom et numéro de téléphone',
                'icon' => 'user'
            ];
        }

        // Photo de profil
        if (empty($user->getAvatar())) {
            $steps[] = [
                'name' => 'Photo de profil',
                'description' => 'Ajoutez une photo pour personnaliser votre profil',
                'icon' => 'camera'
            ];
        }

        // Documents d'identité
        $identityDocs = $user->getDocuments()->filter(function($doc) {
            return $doc->getType() === 'identity';
        });
        if ($identityDocs->count() < 2) {
            $steps[] = [
                'name' => 'Justificatifs d\'identité',
                'description' => 'Pièce d\'identité et selfie avec document',
                'icon' => 'id-card'
            ];
        }

        // Documents financiers
        $financialDocs = $user->getDocuments()->filter(function($doc) {
            return $doc->getType() === 'financial';
        });
        if ($financialDocs->count() === 0) {
            $steps[] = [
                'name' => 'Justificatifs financiers',
                'description' => 'Documents prouvant votre capacité financière',
                'icon' => 'file-invoice'
            ];
        }

        // Conditions d'utilisation
        if (!$user->isTermsAccepted()) {
            $steps[] = [
                'name' => 'Conditions d\'utilisation',
                'description' => 'Acceptez les conditions pour finaliser votre profil',
                'icon' => 'check-square'
            ];
        }

        return $steps;
    }
}
