<?php

namespace App\Messenger\Message;

class SortiePubliee
{
    public function __construct(
        public readonly string $id,
    ) {
    }
}
