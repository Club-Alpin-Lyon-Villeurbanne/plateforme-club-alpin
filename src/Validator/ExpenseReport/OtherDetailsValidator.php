<?php

namespace App\Validator\ExpenseReport;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class OtherDetailsValidator
{
    public function __construct(
        private AttachmentValidator $attachmentValidator
    ) {
    }

    public function validate($others, Collection $attachments, ExecutionContextInterface $context)
    {
        if (!\is_array($others)) {
            $context->buildViolation('Other expenses must be an array.')
                ->atPath('details.others')
                ->addViolation();

            return;
        }

        $requiredAttachments = [];
        foreach ($others as $index => $expense) {
            if (!isset($expense['expenseId'])) {
                $context->buildViolation('Other expense expenseId is missing.')
                    ->atPath("details.others[{$index}].expenseId")
                    ->addViolation();
            }

            if (!isset($expense['price'])) {
                $context->buildViolation('Other expense price is missing.')
                    ->atPath("details.others[{$index}].amount")
                    ->addViolation();
            }
            if (isset($expense['expenseId']) && in_array('price', $expense, true) && $expense['price'] > 0) {
                $requiredAttachments[] = $expense['expenseId'];
            }
        }

        $this->attachmentValidator->validate($attachments, $requiredAttachments, $context);
    }
}
