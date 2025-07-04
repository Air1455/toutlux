<?php

namespace App\Message;

class SendNotificationMessage
{
    public function __construct(
        private int $notificationId
    ) {}

    public function getNotificationId(): int
    {
        return $this->notificationId;
    }
}
