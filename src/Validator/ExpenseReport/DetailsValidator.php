<?php

namespace App\Validator\ExpenseReport;

use App\Entity\ExpenseReport;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class DetailsValidator
{
    private $transportValidator;
    private $accommodationsValidator;
    private $othersValidator;

    public function __construct(
        TransportDetailsValidator $transportValidator,
        AccommodationDetailsValidator $accommodationsValidator,
        OtherDetailsValidator $othersValidator
    ) {
        $this->transportValidator = $transportValidator;
        $this->accommodationsValidator = $accommodationsValidator;
        $this->othersValidator = $othersValidator;
    }

    public function validate(ExpenseReport $expenseReport, ExecutionContextInterface $context)
    {
        $details = json_decode($expenseReport->getDetails(), true);
        $attachments = $expenseReport->getAttachments();

        if (!isset($details['transport'])) {
            $context->buildViolation('Transport details are missing.')
                ->atPath('details.transport')
                ->addViolation();
        } else {
            $this->transportValidator->validate($details['transport'], $attachments, $context);
        }

        if (!isset($details['accommodations'])) {
            $context->buildViolation('Accommodations details are missing.')
                ->atPath('details.accommodations')
                ->addViolation();
        } else {
            $this->accommodationsValidator->validate($details['accommodations'], $attachments, $context);
        }

        if (!isset($details['others'])) {
            $context->buildViolation('Other expenses details are missing.')
                ->atPath('details.others')
                ->addViolation();
        } else {
            $this->othersValidator->validate($details['others'], $attachments, $context);
        }
    }
}
