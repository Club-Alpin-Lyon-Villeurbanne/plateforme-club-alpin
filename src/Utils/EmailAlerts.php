<?php

namespace App\Utils;

use App\Entity\AlertType;

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
    ];
    public const DEFAULT_ALERTS_JSON = '{"vie-du-club":{"Article":true,"Sortie":true},"formation":{"Article":true,"Sortie":true}}';
}
