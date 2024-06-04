<?php

namespace App\Utils\Error;

class ExpenseReportFormError implements \JsonSerializable
{
    public function __construct(
        private string $message,
        private ?string $fieldSlug = null,
        private ?int $expenseTypeId = null,
        private ?string $expenseGroupSlug = null
    ) {
    }

    public function jsonSerialize(): mixed
    {
        return [
            'message' => $this->message,
            'field' => $this->fieldSlug,
            'expenseTypeId' => $this->expenseTypeId,
            'expenseGroup' => $this->expenseGroupSlug,
        ];
    }
}
