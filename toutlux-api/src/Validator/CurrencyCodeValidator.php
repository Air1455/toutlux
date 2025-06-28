<?php

// src/Validator/CurrencyCodeValidator.php
namespace App\Validator;

use App\Service\CurrencyService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class CurrencyCodeValidator extends ConstraintValidator
{
    public function __construct(private CurrencyService $currencyService)
    {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof CurrencyCode) {
            throw new UnexpectedTypeException($constraint, CurrencyCode::class);
        }

        // Null et chaînes vides sont valides
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        // Utilisation du service pour la validation
        $normalizedCurrency = strtoupper(trim($value));

        if (!$this->currencyService->isValidCurrency($normalizedCurrency)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
