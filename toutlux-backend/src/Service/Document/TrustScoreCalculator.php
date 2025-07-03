<?php

namespace App\Service\Document;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class TrustScoreCalculator
{
    private const SCORE_WEIGHTS = [
        'email_verified' => 0.5,      // 0.5 point
        'phone_verified' => 0.5,      // 0.5 point
        'personal_info' => 0.5,       // 0.5 point
        'avatar' => 0.5,              // 0.5 point
        'identity_documents' => 1.5,  // 1.5 points
        'financial_documents' => 1.0, // 1 point
        'terms_accepted' => 0.5,      // 0.5 point
    ];

    private array $scoreWeights;

    public function __construct(
        private EntityManagerInterface $entityManager,
        array $scoreConfig = []
    ) {
        $this->scoreWeights = array_merge(self::SCORE_WEIGHTS, $scoreConfig);
    }

    /**
     * Calculer et mettre à jour le score de confiance d'un utilisateur
     */
    public function updateUserTrustScore(User $user): float
    {
        $score = $this->calculateTrustScore($user);

        $user->setTrustScore($score);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $score;
    }

    /**
     * Calculer le score de confiance sans sauvegarder
     */
    public function calculateTrustScore(User $user): float
    {
        $score = 0.0;

        // Email vérifié
        if ($user->isVerified()) {
            $score += self::SCORE_WEIGHTS['email_verified'];
        }

        // Téléphone vérifié (à implémenter)
        if ($user->isPhoneVerified()) {
            $score += self::SCORE_WEIGHTS['phone_verified'];
        }

        // Informations personnelles complètes
        if ($this->hasCompletePersonalInfo($user)) {
            $score += self::SCORE_WEIGHTS['personal_info'];
        }

        // Photo de profil
        if (!empty($user->getAvatar())) {
            $score += self::SCORE_WEIGHTS['avatar'];
        }

        // Documents d'identité validés
        if ($this->hasValidatedIdentityDocuments($user)) {
            $score += self::SCORE_WEIGHTS['identity_documents'];
        }

        // Documents financiers validés
        if ($this->hasValidatedFinancialDocuments($user)) {
            $score += self::SCORE_WEIGHTS['financial_documents'];
        }

        // Conditions acceptées
        if ($user->isTermsAccepted()) {
            $score += self::SCORE_WEIGHTS['terms_accepted'];
        }

        // Arrondir à 1 décimale et s'assurer que le score est entre 0 et 5
        return round(min(5.0, max(0.0, $score)), 1);
    }

    /**
     * Obtenir le détail du score
     */
    public function getTrustScoreDetails(User $user): array
    {
        $details = [];
        $currentScore = 0.0;

        // Email vérifié
        $emailVerified = $user->isVerified();
        $details['email_verified'] = [
            'completed' => $emailVerified,
            'points' => self::SCORE_WEIGHTS['email_verified'],
            'earned' => $emailVerified ? self::SCORE_WEIGHTS['email_verified'] : 0,
            'label' => 'Email vérifié'
        ];
        if ($emailVerified) {
            $currentScore += self::SCORE_WEIGHTS['email_verified'];
        }

        // Téléphone vérifié
        $phoneVerified = $user->isPhoneVerified();
        $details['phone_verified'] = [
            'completed' => $phoneVerified,
            'points' => self::SCORE_WEIGHTS['phone_verified'],
            'earned' => $phoneVerified ? self::SCORE_WEIGHTS['phone_verified'] : 0,
            'label' => 'Téléphone vérifié'
        ];
        if ($phoneVerified) {
            $currentScore += self::SCORE_WEIGHTS['phone_verified'];
        }

        // Informations personnelles
        $personalInfo = $this->hasCompletePersonalInfo($user);
        $details['personal_info'] = [
            'completed' => $personalInfo,
            'points' => self::SCORE_WEIGHTS['personal_info'],
            'earned' => $personalInfo ? self::SCORE_WEIGHTS['personal_info'] : 0,
            'label' => 'Informations personnelles complètes'
        ];
        if ($personalInfo) {
            $currentScore += self::SCORE_WEIGHTS['personal_info'];
        }

        // Avatar
        $hasAvatar = !empty($user->getAvatar());
        $details['avatar'] = [
            'completed' => $hasAvatar,
            'points' => self::SCORE_WEIGHTS['avatar'],
            'earned' => $hasAvatar ? self::SCORE_WEIGHTS['avatar'] : 0,
            'label' => 'Photo de profil'
        ];
        if ($hasAvatar) {
            $currentScore += self::SCORE_WEIGHTS['avatar'];
        }

        // Documents d'identité
        $identityDocs = $this->hasValidatedIdentityDocuments($user);
        $details['identity_documents'] = [
            'completed' => $identityDocs,
            'points' => self::SCORE_WEIGHTS['identity_documents'],
            'earned' => $identityDocs ? self::SCORE_WEIGHTS['identity_documents'] : 0,
            'label' => 'Documents d\'identité validés',
            'sub_requirements' => $this->getIdentityDocumentsStatus($user)
        ];
        if ($identityDocs) {
            $currentScore += self::SCORE_WEIGHTS['identity_documents'];
        }

        // Documents financiers
        $financialDocs = $this->hasValidatedFinancialDocuments($user);
        $details['financial_documents'] = [
            'completed' => $financialDocs,
            'points' => self::SCORE_WEIGHTS['financial_documents'],
            'earned' => $financialDocs ? self::SCORE_WEIGHTS['financial_documents'] : 0,
            'label' => 'Documents financiers validés'
        ];
        if ($financialDocs) {
            $currentScore += self::SCORE_WEIGHTS['financial_documents'];
        }

        // Conditions acceptées
        $termsAccepted = $user->isTermsAccepted();
        $details['terms_accepted'] = [
            'completed' => $termsAccepted,
            'points' => self::SCORE_WEIGHTS['terms_accepted'],
            'earned' => $termsAccepted ? self::SCORE_WEIGHTS['terms_accepted'] : 0,
            'label' => 'Conditions d\'utilisation acceptées'
        ];
        if ($termsAccepted) {
            $currentScore += self::SCORE_WEIGHTS['terms_accepted'];
        }

        return [
            'current_score' => round($currentScore, 1),
            'max_score' => 5.0,
            'percentage' => round(($currentScore / 5.0) * 100, 0),
            'details' => $details,
            'next_steps' => $this->getNextSteps($details)
        ];
    }

    /**
     * Vérifier si les informations personnelles sont complètes
     */
    private function hasCompletePersonalInfo(User $user): bool
    {
        return !empty($user->getFirstName())
            && !empty($user->getLastName())
            && !empty($user->getPhone())
            && !empty($user->getBirthDate());
    }

    /**
     * Vérifier si les documents d'identité sont validés
     */
    private function hasValidatedIdentityDocuments(User $user): bool
    {
        $documents = $user->getDocuments();

        $hasIdCard = false;
        $hasSelfie = false;

        foreach ($documents as $document) {
            if ($document->getType() === 'identity' && $document->getStatus() === 'validated') {
                if ($document->getSubType() === 'id_card') {
                    $hasIdCard = true;
                } elseif ($document->getSubType() === 'selfie') {
                    $hasSelfie = true;
                }
            }
        }

        return $hasIdCard && $hasSelfie;
    }

    /**
     * Obtenir le statut des documents d'identité
     */
    private function getIdentityDocumentsStatus(User $user): array
    {
        $status = [
            'id_card' => ['required' => true, 'uploaded' => false, 'validated' => false],
            'selfie' => ['required' => true, 'uploaded' => false, 'validated' => false]
        ];

        foreach ($user->getDocuments() as $document) {
            if ($document->getType() === 'identity') {
                $subType = $document->getSubType();
                if (isset($status[$subType])) {
                    $status[$subType]['uploaded'] = true;
                    $status[$subType]['validated'] = $document->getStatus() === 'validated';
                }
            }
        }

        return $status;
    }

    /**
     * Vérifier si les documents financiers sont validés
     */
    private function hasValidatedFinancialDocuments(User $user): bool
    {
        $documents = $user->getDocuments();

        foreach ($documents as $document) {
            if ($document->getType() === 'financial' && $document->getStatus() === 'validated') {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtenir les prochaines étapes pour améliorer le score
     */
    private function getNextSteps(array $details): array
    {
        $steps = [];

        foreach ($details as $key => $detail) {
            if (!$detail['completed'] && $detail['points'] > 0) {
                $steps[] = [
                    'key' => $key,
                    'label' => $detail['label'],
                    'points' => $detail['points'],
                    'priority' => $this->getStepPriority($key)
                ];
            }
        }

        // Trier par priorité
        usort($steps, function($a, $b) {
            return $b['priority'] - $a['priority'];
        });

        return array_slice($steps, 0, 3); // Retourner les 3 prochaines étapes
    }

    /**
     * Obtenir la priorité d'une étape
     */
    private function getStepPriority(string $key): int
    {
        $priorities = [
            'email_verified' => 10,
            'personal_info' => 9,
            'terms_accepted' => 8,
            'avatar' => 7,
            'identity_documents' => 6,
            'phone_verified' => 5,
            'financial_documents' => 4,
        ];

        return $priorities[$key] ?? 0;
    }

    /**
     * Obtenir le label du niveau de confiance
     */
    public function getTrustLevel(float $score): array
    {
        if ($score >= 4.5) {
            return [
                'level' => 'excellent',
                'label' => 'Excellent',
                'color' => 'success',
                'icon' => 'shield-check'
            ];
        } elseif ($score >= 3.5) {
            return [
                'level' => 'good',
                'label' => 'Bon',
                'color' => 'primary',
                'icon' => 'shield'
            ];
        } elseif ($score >= 2.5) {
            return [
                'level' => 'medium',
                'label' => 'Moyen',
                'color' => 'warning',
                'icon' => 'shield-exclamation'
            ];
        } elseif ($score >= 1.5) {
            return [
                'level' => 'low',
                'label' => 'Faible',
                'color' => 'danger',
                'icon' => 'shield-x'
            ];
        } else {
            return [
                'level' => 'very_low',
                'label' => 'Très faible',
                'color' => 'secondary',
                'icon' => 'shield-off'
            ];
        }
    }
}
