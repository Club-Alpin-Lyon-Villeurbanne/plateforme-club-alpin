<?php

namespace App\Validator\ExpenseReport;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class TransportDetailsValidator
{
    private const VALID_TYPES = [
        'PERSONAL_VEHICLE',
        'CLUB_MINIBUS',
        'RENTAL_MINIBUS',
        'PUBLIC_TRANSPORT',
    ];

    private const REQUIRED_FIELDS = [
        'PERSONAL_VEHICLE' => ['distance'],
        'CLUB_MINIBUS' => ['fuelExpense', 'distance', 'passengerCount'],
        'RENTAL_MINIBUS' => ['passengerCount', 'fuelExpense', 'rentalPrice'],
        'PUBLIC_TRANSPORT' => ['ticketPrice'],
    ];

    private const REQUIRED_ATTACHMENTS = [
        'PERSONAL_VEHICLE' => [],
        'CLUB_MINIBUS' => ['fuelExpense'],
        'RENTAL_MINIBUS' => ['fuelExpense', 'rentalPrice'],
        'PUBLIC_TRANSPORT' => ['ticketPrice'],
    ];

    public function __construct(
        private AttachmentValidator $attachmentValidator
    ) {
    }

    public function validate($transport, Collection $attachments, ExecutionContextInterface $context): void
    {
        if (!$this->isValidTransportObject($transport, $context)) {
            return;
        }

        $type = $transport['type'];
        if (!$this->isValidTransportType($type, $context)) {
            return;
        }

        $this->validateRequiredFields($transport, $type, $context);
        $this->validateRequiredAttachments($attachments, $type, $context);
    }

    private function isValidTransportObject($transport, ExecutionContextInterface $context): bool
    {
        if (!\is_array($transport)) {
            $context->buildViolation('Transport details must be an object.')
                ->atPath('details.transport')
                ->addViolation();
            return false;
        }

        if (!isset($transport['type'])) {
            $context->buildViolation('Transport type is missing.')
                ->atPath('details.transport.type')
                ->addViolation();
            return false;
        }

        return true;
    }

    private function isValidTransportType(string $type, ExecutionContextInterface $context): bool
    {
        if (!\in_array($type, self::VALID_TYPES, true)) {
            $context->buildViolation('Invalid transport type.')
                ->atPath('details.transport.type')
                ->addViolation();
            return false;
        }

        return true;
    }

    private function validateRequiredFields(array $transport, string $type, ExecutionContextInterface $context): void
    {
        $requiredFields = self::REQUIRED_FIELDS[$type] ?? [];
        
        foreach ($requiredFields as $field) {
            if (!isset($transport[$field])) {
                $context->buildViolation("{$field} is required for {$type}.")
                    ->atPath("details.transport.{$field}")
                    ->addViolation();
            }
        }
    }

    private function validateRequiredAttachments(Collection $attachments, string $type, ExecutionContextInterface $context): void
    {
        $requiredAttachments = self::REQUIRED_ATTACHMENTS[$type] ?? [];
        $this->attachmentValidator->validate($attachments, $requiredAttachments, $context);
    }
}
