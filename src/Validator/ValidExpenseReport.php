<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ValidExpenseReport extends Constraint
{
    public $message = 'The expense report is not valid.';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
