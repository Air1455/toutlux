<?php

namespace App\DTO\Auth;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterRequest
{
    #[Assert\NotBlank(message: 'L\'email est requis')]
    #[Assert\Email(message: 'Email invalide')]
    public string $email;

    #[Assert\NotBlank(message: 'Le mot de passe est requis')]
    #[Assert\Length(
        min: 8,
        minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères'
    )]
    #[Assert\Regex(
        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
        message: 'Le mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre'
    )]
    public string $password;

    #[Assert\NotBlank(message: 'Le prénom est requis')]
    #[Assert\Length(max: 100)]
    public string $firstName;

    #[Assert\NotBlank(message: 'Le nom est requis')]
    #[Assert\Length(max: 100)]
    public string $lastName;

    #[Assert\Length(max: 20)]
    #[Assert\Regex(
        pattern: '/^[0-9\+\-\.\(\)\s]+$/',
        message: 'Numéro de téléphone invalide'
    )]
    public ?string $phoneNumber = null;
}
