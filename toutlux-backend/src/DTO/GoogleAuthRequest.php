<?php

namespace App\DTO\Auth;

use Symfony\Component\Validator\Constraints as Assert;

class GoogleAuthRequest
{
    #[Assert\NotBlank(message: 'Le token Google est requis')]
    public string $idToken;
}
