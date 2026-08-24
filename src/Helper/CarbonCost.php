<?php

declare(strict_types=1);

namespace App\Helper;

final readonly class CarbonCost
{
    public function __construct(
        public float $total,
        public float $perPerson,
    ) {
    }
}
