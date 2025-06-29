<?php

namespace App\Service;

use App\Entity\User;

class ListingPermissionService
{
    public function isListingCreationAllowed(User $user): bool
    {
        return $user->isEmailVerified() &&
            $user->isPhoneVerified() &&
            $user->isIdentityVerified() &&
            $user->getStatus() === 'active' &&
            $user->isTermsAccepted() &&
            $user->isPrivacyAccepted();
    }
}
