<?php

namespace App\Validator\ExpenseReport;

use App\Entity\ExpenseAttachment;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class AttachmentValidator
{
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

        // if (!$attachmentExists) {
        //     $context->buildViolation('Attachment is missing for expense with ID \'{expenseId}\'.')
        //         ->setParameter('{expenseId}', $expenseId)
        //         ->addViolation();
        // }
    }
}
