<?php

namespace App\DTO\Auth;

use Symfony\Component\Validator\Constraints as Assert;

class LoginRequest
{
    #[Assert\NotBlank(message: 'L\'email est requis')]
    #[Assert\Email(message: 'Email invalide')]
    public string $email;

    #[Assert\NotBlank(message: 'Le mot de passe est requis')]
    public string $password;
}
