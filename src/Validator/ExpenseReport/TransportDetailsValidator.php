<?php

namespace App\Validator\ExpenseReport;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class TransportDetailsValidator
{
    public function __construct(
        private AttachmentValidator $attachmentValidator
    ) {
    }

    public function validate($transport, Collection $attachments, ExecutionContextInterface $context)
    {
        if (!\is_array($transport)) {
            $context->buildViolation('Transport details must be an object.')
                ->atPath('details.transport')
                ->addViolation();

            return;
        }

        if (!isset($transport['type'])) {
            $context->buildViolation('Transport type is missing.')
                ->atPath('details.transport.type')
                ->addViolation();

            return;
        }

        $validTypes = ['PERSONAL_VEHICLE', 'CLUB_MINIBUS', 'RENTAL_MINIBUS', 'PUBLIC_TRANSPORT'];
        if (!\in_array($transport['type'], $validTypes, true)) {
            $context->buildViolation('Invalid transport type.')
                ->atPath('details.transport.type')
                ->addViolation();

            return;
        }

        $requiredFields = $this->getRequiredFieldsForTransportType($transport['type']);

        foreach ($requiredFields as $field) {
            if (!isset($transport[$field])) {
                $context->buildViolation("{$field} is required for {$transport['type']}.")
                    ->atPath("details.transport.{$field}")
                    ->addViolation();
            }
        }

        $requiredAttachments = $this->getRequiredAttachmentsForTransportType($transport['type']);
        $this->attachmentValidator->validate($attachments, $requiredAttachments, $context);
    }

    private function getRequiredAttachmentsForTransportType(string $type): array
    {
        switch ($type) {
            case 'PERSONAL_VEHICLE':
                return ['tollFee'];
            case 'CLUB_MINIBUS':
                return ['fuelExpense', 'tollFee'];
            case 'RENTAL_MINIBUS':
                return ['tollFee', 'fuelExpense', 'rentalPrice'];
            case 'PUBLIC_TRANSPORT':
                return ['ticketPrice'];
            default:
                return [];
        }
    }

    private function getRequiredFieldsForTransportType(string $type): array
    {
        switch ($type) {
            case 'PERSONAL_VEHICLE':
                return ['distance', 'tollFee'];
            case 'CLUB_MINIBUS':
                return ['fuelExpense', 'tollFee', 'distance', 'passengerCount'];
            case 'RENTAL_MINIBUS':
                return ['tollFee', 'passengerCount', 'fuelExpense', 'rentalPrice'];
            case 'PUBLIC_TRANSPORT':
                return ['ticketPrice'];
            default:
                return [];
        }
    }
}
