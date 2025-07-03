<?php

namespace App\DTO\Profile;

use Symfony\Component\Validator\Constraints as Assert;

class ProfileUpdateRequest
{
    #[Assert\Length(max: 100)]
    public ?string $firstName = null;

    #[Assert\Length(max: 100)]
    public ?string $lastName = null;

    #[Assert\Length(max: 20)]
    #[Assert\Regex(
        pattern: '/^[0-9\+\-\.\(\)\s]+$/',
        message: 'Numéro de téléphone invalide'
    )]
    public ?string $phone = null;

    #[Assert\Date]
    public ?string $birthDate = null;

    #[Assert\Length(max: 255)]
    public ?string $address = null;

    #[Assert\Length(max: 100)]
    public ?string $city = null;

    #[Assert\Length(max: 10)]
    #[Assert\Regex(
        pattern: '/^[0-9]{5}$/',
        message: 'Code postal invalide'
    )]
    public ?string $postalCode = null;

    #[Assert\Length(
        min: 2,
        max: 2,
        exactMessage: 'Le code pays doit contenir exactement {{ limit }} caractères'
    )]
    public ?string $country = null;

    #[Assert\Length(max: 1000)]
    public ?string $bio = null;

    public ?bool $emailNotificationsEnabled = null;

    public ?bool $smsNotificationsEnabled = null;
}
