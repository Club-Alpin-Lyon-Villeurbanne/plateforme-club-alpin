<?php

namespace App\Utils;

use App\Entity\AlertType;

class EmailAlerts
{
    public const DEFAULT_ALERTS = [
        'all' => [
            AlertType::ArticlePush->name => true,
        ],
        'vie-du-club' => [
            AlertType::Article->name => true,
            AlertType::Sortie->name => true,
            AlertType::SortiePush->name => false,
        ],
        'formation' => [
            AlertType::Article->name => true,
            AlertType::Sortie->name => true,
            AlertType::SortiePush->name => false,
        ],
    ];
    public const DEFAULT_ALERTS_JSON = '{"all": {"ArticlePush": true},"vie-du-club":{"Article":true,"Sortie":true, "SortiePush": true},"formation":{"Article":true,"Sortie":true, "SortiePush": true}}';
}
