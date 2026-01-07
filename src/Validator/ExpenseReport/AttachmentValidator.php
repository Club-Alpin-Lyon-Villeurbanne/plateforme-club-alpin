<?php

namespace App\Validator\ExpenseReport;

use App\Entity\ExpenseAttachment;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class AttachmentValidator
{
    private const EXPENSE_LABELS = [
        'fuelExpense' => 'Carburant',
        'rentalPrice' => 'Location',
        'ticketPrice' => 'Billet de transport',
        'tollExpense' => 'Péage',
    ];

    public function validate(Collection $attachments, array $requiredAttachments, ExecutionContextInterface $context)
    {
        foreach ($requiredAttachments as $expenseId) {
            $this->validateAttachmentExistence($attachments, $expenseId, $context);
        }
    }

    private function validateAttachmentExistence(Collection $attachments, string $expenseId, ExecutionContextInterface $context)
    {
        $attachmentExists = $attachments->exists(function ($key, ExpenseAttachment $attachment) use ($expenseId) {
            return $attachment->getExpenseId() === $expenseId;
        });

        if (!$attachmentExists) {
            $label = self::EXPENSE_LABELS[$expenseId] ?? $expenseId;
            $context->buildViolation('Un justificatif est requis pour le champ « {label} ».')
                ->setParameter('{label}', $label)
                ->addViolation();
        }
    }
}
