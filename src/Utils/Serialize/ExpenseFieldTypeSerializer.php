<?php

namespace App\Utils\Serialize;

use App\Entity\ExpenseFieldType;

class ExpenseFieldTypeSerializer {

    public static function serialize(ExpenseFieldType $expenseFieldType): ?array
    {
        return [
            'id' => $expenseFieldType->getId(),
            'name' => $expenseFieldType->getName(),
            'slug' => $expenseFieldType->getSlug(),
            'inputType' => $expenseFieldType->getInputType(),
            // property set manually in SortieController.php
            'flags' => $expenseFieldType->getFlags(),
        ];
    }
}
