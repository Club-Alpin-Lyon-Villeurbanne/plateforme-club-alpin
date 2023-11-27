<?php

namespace App\Utils\Enums;

enum ExpenseReportEnum: int
{
    public const STATUS_DRAFT = 0;
    public const STATUS_PENDING = 1;
    public const STATUS_REFUSED = 2;
    public const STATUS_PAID = 3;
}
