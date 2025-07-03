<?php

namespace App\DTO\Auth;

use Symfony\Component\Validator\Constraints as Assert;

class RefreshTokenRequest
{
    #[Assert\NotBlank(message: 'Le refresh token est requis')]
    public string $refreshToken;
}
