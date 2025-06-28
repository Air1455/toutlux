<?php

namespace App\Event;

use App\Entity\User;
use App\Enum\UserStatus;
use Symfony\Contracts\EventDispatcher\Event;

class UserRegisteredEvent extends Event
{
    public function __construct(
        private User $user
    ) {}

    public function getUser(): User
    {
        return $this->user;
    }
}
