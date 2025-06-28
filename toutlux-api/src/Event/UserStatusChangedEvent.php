<?php

namespace App\Event;

use App\Entity\User;
use App\Enum\UserStatus;
use Symfony\Contracts\EventDispatcher\Event;

class UserStatusChangedEvent extends Event
{
    public function __construct(
        private User $user,
        private UserStatus $newStatus
    ) {}

    public function getUser(): User
    {
        return $this->user;
    }

    public function getNewStatus(): UserStatus
    {
        return $this->newStatus;
    }
}
