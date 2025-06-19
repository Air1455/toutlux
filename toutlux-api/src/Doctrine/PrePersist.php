<?php

namespace App\Doctrine;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsDoctrineListener(event: Events::prePersist, priority: 500, connection: 'default')]
readonly class PrePersist
{

    public function __construct(
        private UserPasswordHasherInterface $passwordHashes,
    ){}

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if($entity instanceof User){
            $hashedPassword= $this->passwordHashes->hashPassword($entity, $entity->getPassword());
            $entity->setPassword($hashedPassword);
        }

    }

}