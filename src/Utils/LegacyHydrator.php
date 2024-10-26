<?php

namespace App\Utils;

use Doctrine\ORM\Internal\Hydration\AbstractHydrator;

class LegacyHydrator extends AbstractHydrator
{
    protected function hydrateAllData(): array
    {
        $result = $this->_stmt->fetchAllAssociative();

        $hydratedResult = [];

        // Remove suffixes put by Doctrine on field names
        foreach ($result as $row) {
            $hydratedRow = [];
            foreach ($row as $key => $value) {
                $newKey = preg_replace('/_\d+$/', '', $key); // Supprime le suffixe numérique
                $hydratedRow[$newKey] = $value;
            }
            $hydratedResult[] = $hydratedRow;
        }

        return $hydratedResult;
    }
}
