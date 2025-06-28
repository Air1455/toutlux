<?php

// src/Validator/CurrencyCode.php
namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class CurrencyCode extends Constraint
{
    public string $message = 'Le code de devise "{{ value }}" n\'est pas valide.';
}
