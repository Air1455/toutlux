<?php

namespace App\Service\User;

use App\Entity\User;
use App\Entity\Document;
use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;

class TrustScoreCalculator
{
    private const SCORE_WEIGHTS = [
        'email_verified' => 0.5,        // 0.5 points
        'personal_info' => 1.0,         // 1 point
        'identity_verified' => 1.5,     // 1.5 points
        'financial_verified' => 1.5,    // 1.5 points
        'terms_accepted' => 0.5,        // 0.5 points
    ];

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function calculateTrustScore(User $user): float
    {
        $score = 0.0;
        $profile = $user->getProfile();

        // Email verification
        if ($user->isVerified()) {
            $score += self::SCORE_WEIGHTS['email_verified'];
        }

        if ($profile) {
            // Personal information completion
            if ($profile->isPersonalInfoComplete() && $profile->isPersonalInfoValidated()) {
                $score += self::SCORE_WEIGHTS['personal_info'];
            }

            // Identity verification
            if ($profile->isIdentityValidated()) {
                $score += self::SCORE_WEIGHTS['identity_verified'];
            }

            // Financial verification
            if ($profile->isFinancialValidated()) {
                $score += self::SCORE_WEIGHTS['financial_verified'];
            }

            // Terms acceptance
            if ($profile->isTermsAccepted()) {
                $score += self::SCORE_WEIGHTS['terms_accepted'];
            }
        }

        // Ensure score is between 0 and 5
        $score = min(5.0, max(0.0, $score));

        return round($score, 1);
    }

    public function updateUserTrustScore(User $user): void
    {
        $oldScore = $user->getTrustScore();
        $newScore = $this->calculateTrustScore($user);

        if ($oldScore !== $newScore) {
            $user->setTrustScore($newScore);
            $this->entityManager->flush();

            // Create notification if score increased
            if ($newScore > $oldScore) {
                $this->createTrustScoreNotification($user, $oldScore, $newScore);
            }
        }
    }

    private function createTrustScoreNotification(User $user, float $oldScore, float $newScore): void
    {
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setType(Notification::TYPE_TRUST_SCORE_UPDATE);
        $notification->setTitle('Trust Score Updated');
        $notification->setContent(sprintf(
            'Your trust score has increased from %.1f to %.1f stars!',
            $oldScore,
            $newScore
        ));
        $notification->setData([
            'old_score' => $oldScore,
            'new_score' => $newScore
        ]);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();
    }

    public function getScoreBreakdown(User $user): array
    {
        $profile = $user->getProfile();
        $breakdown = [];

        // Email verification
        $breakdown['email_verified'] = [
            'completed' => $user->isVerified(),
            'points' => self::SCORE_WEIGHTS['email_verified'],
            'earned' => $user->isVerified() ? self::SCORE_WEIGHTS['email_verified'] : 0
        ];

        if ($profile) {
            // Personal info
            $breakdown['personal_info'] = [
                'completed' => $profile->isPersonalInfoComplete() && $profile->isPersonalInfoValidated(),
                'points' => self::SCORE_WEIGHTS['personal_info'],
                'earned' => ($profile->isPersonalInfoComplete() && $profile->isPersonalInfoValidated())
                    ? self::SCORE_WEIGHTS['personal_info'] : 0
            ];

            // Identity
            $breakdown['identity_verified'] = [
                'completed' => $profile->isIdentityValidated(),
                'points' => self::SCORE_WEIGHTS['identity_verified'],
                'earned' => $profile->isIdentityValidated() ? self::SCORE_WEIGHTS['identity_verified'] : 0
            ];

            // Financial
            $breakdown['financial_verified'] = [
                'completed' => $profile->isFinancialValidated(),
                'points' => self::SCORE_WEIGHTS['financial_verified'],
                'earned' => $profile->isFinancialValidated() ? self::SCORE_WEIGHTS['financial_verified'] : 0
            ];

            // Terms
            $breakdown['terms_accepted'] = [
                'completed' => $profile->isTermsAccepted(),
                'points' => self::SCORE_WEIGHTS['terms_accepted'],
                'earned' => $profile->isTermsAccepted() ? self::SCORE_WEIGHTS['terms_accepted'] : 0
            ];
        } else {
            // Default values if no profile
            foreach (['personal_info', 'identity_verified', 'financial_verified', 'terms_accepted'] as $key) {
                $breakdown[$key] = [
                    'completed' => false,
                    'points' => self::SCORE_WEIGHTS[$key],
                    'earned' => 0
                ];
            }
        }

        return $breakdown;
    }

    public function getNextSteps(User $user): array
    {
        $steps = [];
        $profile = $user->getProfile();

        if (!$user->isVerified()) {
            $steps[] = [
                'action' => 'verify_email',
                'description' => 'Verify your email address',
                'points' => self::SCORE_WEIGHTS['email_verified']
            ];
        }

        if ($profile) {
            if (!$profile->isPersonalInfoComplete()) {
                $steps[] = [
                    'action' => 'complete_personal_info',
                    'description' => 'Complete your personal information',
                    'points' => self::SCORE_WEIGHTS['personal_info']
                ];
            }

            if (!$profile->isIdentityValidated()) {
                $steps[] = [
                    'action' => 'verify_identity',
                    'description' => 'Upload identity documents',
                    'points' => self::SCORE_WEIGHTS['identity_verified']
                ];
            }

            if (!$profile->isFinancialValidated()) {
                $steps[] = [
                    'action' => 'verify_financial',
                    'description' => 'Upload financial documents',
                    'points' => self::SCORE_WEIGHTS['financial_verified']
                ];
            }

            if (!$profile->isTermsAccepted()) {
                $steps[] = [
                    'action' => 'accept_terms',
                    'description' => 'Accept terms and conditions',
                    'points' => self::SCORE_WEIGHTS['terms_accepted']
                ];
            }
        }

        // Sort by points (highest first)
        usort($steps, fn($a, $b) => $b['points'] <=> $a['points']);

        return $steps;
    }
}
