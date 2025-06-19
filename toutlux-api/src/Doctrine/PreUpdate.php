<?php

namespace App\Doctrine;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsDoctrineListener(event: Events::preUpdate, priority: 500, connection: 'default')]
readonly class PreUpdate
{

    public function __construct(
        private UserPasswordHasherInterface $passwordHashes,
    ){}

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if($entity instanceof User){
            $hashedPassword= $this->passwordHashes->hashPassword($entity, $entity->getPassword());
            $entity->setPassword($hashedPassword);
        }

    }

}
