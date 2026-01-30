<?php

namespace App\Helper;

use App\Entity\Evt;

class DistanceHelper
{
    // @todo calculer distance aller-retour
    public function calculate(Evt $event): float
    {
        $start = $event->getRdv();
        $end = $event->getPlace();

        return 130 * 2;
    }
}
