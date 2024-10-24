<?php

namespace App\Messenger\Message;

use App\Entity\AlertType;

class UserNotification
{
    public function __construct(
        public readonly AlertType $alertType,
        public readonly string $id,
        public readonly string $userId
    ) {
    }
}
