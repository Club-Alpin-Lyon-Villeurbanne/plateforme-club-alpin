<?php

namespace App\Validator\ExpenseReport;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class AccommodationDetailsValidator
{
    public function __construct(
        private AttachmentValidator $attachmentValidator
    ) {
    }

    public function validate($accommodations, Collection $attachments, ExecutionContextInterface $context)
    {
        if (!\is_array($accommodations)) {
            $context->buildViolation('Accommodations must be an array.')
                ->atPath('details.accommodations')
                ->addViolation();

            return;
        }

        $requiredAttachments = [];

        foreach ($accommodations as $index => $accommodation) {
            if (!isset($accommodation['expenseId'])) {
                $context->buildViolation('Accommodation expenseId is missing.')
                    ->atPath("details.accommodations[{$index}].expenseId")
                    ->addViolation();
            }
            if (!isset($accommodation['price'])) {
                $context->buildViolation('Accommodation price is missing.')
                    ->atPath("details.accommodations[{$index}].price")
                    ->addViolation();
            }

            if (isset($accommodation['expenseId']) && in_array('price', $accommodation, true) && $accommodation['price'] > 0) {
                $requiredAttachments[] = $accommodation['expenseId'];
            }
        }

        $this->attachmentValidator->validate($attachments, $requiredAttachments, $context);
    }
}
