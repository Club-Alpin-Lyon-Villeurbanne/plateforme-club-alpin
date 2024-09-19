<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ExpenseReportCreateDto
{
    #[Assert\NotBlank]
    #[Assert\Positive]
    public $eventId;
}
