<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ValidExpenseReportDetails extends Constraint
{
    public $message = 'The details structure is not valid.';
}
