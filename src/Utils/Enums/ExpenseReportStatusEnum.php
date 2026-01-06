<?php

namespace App\Utils\Enums;

enum ExpenseReportStatusEnum: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case REJECTED = 'rejected';
    case APPROVED = 'approved';
    case ACCOUNTED = 'accounted';
}
