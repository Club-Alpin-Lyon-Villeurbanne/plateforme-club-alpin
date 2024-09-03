<?php

namespace App\Utils\Enums;

enum ExpenseReportStatusEnum: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case REJECTED = 'rejected';
    case APPROVED = 'approved';

    // public static function case(): array
    // {
    //     return array_column(self::cases(), 'value');
    // }
}
