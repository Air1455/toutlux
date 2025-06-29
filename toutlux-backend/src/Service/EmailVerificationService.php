<?php

namespace App\Service;

use App\Entity\User;

class EmailVerificationService
{
    public function isGmailAccount(User $user): bool
    {
        return str_ends_with(strtolower($user->getEmail() ?? ''), '@gmail.com');
    }

    public function isEmailConfirmationRequired(User $user): bool
    {
        return !$user->isEmailVerified() && !$this->isGmailAccount($user);
    }

    public function isEmailConfirmationRequestAllowed(User $user): bool
    {
        if ($user->isEmailVerified()) {
            return false;
        }

        $lastRequest = $user->getLastEmailVerificationRequestAt();
        if ($lastRequest && $lastRequest > new \DateTimeImmutable('-1 hour')) {
            $attempts = $user->getEmailVerificationAttempts() ?? 0;
            return $attempts < 3;
        }

        return true;
    }

    public function getNextEmailConfirmationAllowedAt(User $user): ?\DateTimeImmutable
    {
        if ($this->isEmailConfirmationRequestAllowed($user)) {
            return null;
        }

        return $user->getLastEmailVerificationRequestAt()?->modify('+1 hour');
    }

    public function isEmailConfirmationTokenExpired(User $user): bool
    {
        if (!$user->getEmailConfirmationTokenExpiresAt()) {
            return true;
        }

        return $user->getEmailConfirmationTokenExpiresAt() <= new \DateTimeImmutable();
    }

    public function getEmailVerificationStatus(User $user): array
    {
        return [
            'is_verified' => $user->isEmailVerified(),
            'is_gmail' => $this->isGmailAccount($user),
            'needs_confirmation' => $this->isEmailConfirmationRequired($user),
            'can_request_new' => $this->isEmailConfirmationRequestAllowed($user),
            'attempts_used' => $user->getEmailVerificationAttempts() ?? 0,
            'next_allowed_at' => $this->getNextEmailConfirmationAllowedAt($user)?->format('c'),
            'token_expires_at' => $user->getEmailConfirmationTokenExpiresAt()?->format('c'),
            'token_expired' => $this->isEmailConfirmationTokenExpired($user)
        ];
    }
}
