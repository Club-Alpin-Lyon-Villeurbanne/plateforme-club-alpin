<?php

namespace App\Utils\Enums;

enum ExpenseReportEnum: int
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_APPROVED = 'approved';

    public static function getConstants(): array
    {
        return [
            'STATUS_DRAFT' => self::STATUS_DRAFT,
            'STATUS_SUBMITTED' => self::STATUS_SUBMITTED,
            'STATUS_REJECTED' => self::STATUS_REJECTED,
            'STATUS_APPROVED' => self::STATUS_APPROVED,
        ];
    }
}
