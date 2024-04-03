<?php

namespace App\Dto;

use ApiPlatform\Api\FilterInterface;

final class ExpenseReportStatusDto implements FilterInterface
{
    public $status;
    public $statusComment;

    public function getDescription(string $resourceClass): array
    {
        return [
            'status' => [
                'property' => 'status',
                'type' => 'string',
                'required' => true,
            ],
            'statusComment' => [
                'property' => 'statusComment',
                'type' => 'string',
                'required' => false,
            ],
        ];
    }
}
