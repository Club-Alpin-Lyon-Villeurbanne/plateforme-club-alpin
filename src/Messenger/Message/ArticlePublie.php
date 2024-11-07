<?php

namespace App\Messenger\Message;

class ArticlePublie
{
    public const ACTU_CLUB_RUBRIQUE = 'actuclub';

    public function __construct(
        public readonly string $id,
    ) {
    }
}
