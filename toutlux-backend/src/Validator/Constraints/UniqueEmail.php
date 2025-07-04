<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class UniqueEmail extends Constraint
{
    public string $message = 'Cet email "{{ value }}" est déjà utilisé.';
    public string $entityClass = 'App\Entity\User';
    public string $field = 'email';
    public bool $ignoreNull = true;

    public function __construct(
        ?string $message = null,
        ?string $entityClass = null,
        ?string $field = null,
        bool $ignoreNull = true,
        ?array $groups = null,
        mixed $payload = null
    ) {
        parent::__construct([], $groups, $payload);

        $this->message = $message ?? $this->message;
        $this->entityClass = $entityClass ?? $this->entityClass;
        $this->field = $field ?? $this->field;
        $this->ignoreNull = $ignoreNull;
    }
}
