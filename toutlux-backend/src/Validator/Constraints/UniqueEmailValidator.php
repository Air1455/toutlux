<?php

namespace App\Validator\Constraints;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueEmailValidator extends ConstraintValidator
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueEmail) {
            throw new UnexpectedTypeException($constraint, UniqueEmail::class);
        }

        if (null === $value || '' === $value) {
            if (!$constraint->ignoreNull) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', 'null')
                    ->addViolation();
            }
            return;
        }

        // Récupérer le repository
        $repository = $this->entityManager->getRepository($constraint->entityClass);

        // Chercher une entité avec cet email
        $existingEntity = $repository->findOneBy([
            $constraint->field => $value
        ]);

        if (!$existingEntity) {
            return;
        }

        // Si on est en mode édition, vérifier que ce n'est pas la même entité
        $object = $this->context->getObject();
        if ($object && method_exists($object, 'getId') && $object->getId()) {
            if ($existingEntity->getId() === $object->getId()) {
                return;
            }
        }

        // L'email existe déjà pour une autre entité
        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $value)
            ->addViolation();
    }
}
