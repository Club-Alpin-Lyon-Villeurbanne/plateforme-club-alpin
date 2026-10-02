<?php

declare(strict_types=1);

namespace App\Entity;

enum AccueilCircuitEnum: string
{
    case NOUVEAUX = 'nouveaux';
    case RENOUVELLEMENTS = 'renouvellements';
}
