<?php

namespace App\Utils\Enums;

enum ExpenseReportEnum: int
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'submitted';
    public const STATUS_REFUSED = 'rejected';
    public const STATUS_PAID = 'approved';
}
