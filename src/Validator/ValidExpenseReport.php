<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ValidExpenseReport extends Constraint
{
    public string $message = 'The expense report is not valid.';

    public function getTargets(): array|string
    {
        return self::CLASS_CONSTRAINT;
    }
}
