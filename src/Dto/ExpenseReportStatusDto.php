<?php

namespace App\Dto;

use ApiPlatform\Metadata\FilterInterface;

final class ExpenseReportStatusDto implements FilterInterface
{
    public string $status;
    public string $statusComment;

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
