<?php

namespace App\Utils;

use App\Entity\AlertType;
use App\Messenger\Message\ArticlePublie;

class EmailAlerts
{
    public const DEFAULT_ALERTS = [
        'vie-du-club' => [
            AlertType::Article->name => true,
            AlertType::Sortie->name => true,
        ],
        'formation' => [
            AlertType::Article->name => true,
            AlertType::Sortie->name => true,
        ],
        ArticlePublie::ACTU_CLUB_RUBRIQUE => [
            AlertType::Article->name => true,
        ],
    ];
    public const DEFAULT_ALERTS_JSON = '{"actuclub":{"Article":true},"vie-du-club":{"Article":true,"Sortie":true},"formation":{"Article":true,"Sortie":true}}';
}
