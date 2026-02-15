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

    private const FIELD_LABELS = [
        'distance' => 'Distance',
        'fuelExpense' => 'Carburant',
        'rentalPrice' => 'Location',
        'ticketPrice' => 'Billet de transport',
        'passengerCount' => 'Nombre de passagers',
        'tollExpense' => 'Péage',
    ];

    private const TYPE_LABELS = [
        'PERSONAL_VEHICLE' => 'véhicule personnel',
        'CLUB_MINIBUS' => 'minibus du club',
        'RENTAL_MINIBUS' => 'minibus de location',
        'PUBLIC_TRANSPORT' => 'transport en commun',
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
        $this->validateRequiredAttachments($attachments, $transport, $type, $context);
    }

    private function isValidTransportObject($transport, ExecutionContextInterface $context): bool
    {
        if (!\is_array($transport)) {
            $context->buildViolation('Les détails du transport sont invalides.')
                ->atPath('details.transport')
                ->addViolation();

            return false;
        }

        if (!isset($transport['type'])) {
            $context->buildViolation('Le type de transport est requis.')
                ->atPath('details.transport.type')
                ->addViolation();

            return false;
        }

        return true;
    }

    private function isValidTransportType(string $type, ExecutionContextInterface $context): bool
    {
        if (!\in_array($type, self::VALID_TYPES, true)) {
            $context->buildViolation('Type de transport invalide.')
                ->atPath('details.transport.type')
                ->addViolation();

            return false;
        }

        return true;
    }

    private function validateRequiredFields(array $transport, string $type, ExecutionContextInterface $context): void
    {
        $requiredFields = self::REQUIRED_FIELDS[$type] ?? [];
        $typeLabel = self::TYPE_LABELS[$type] ?? $type;

        foreach ($requiredFields as $field) {
            if (!isset($transport[$field])) {
                $fieldLabel = self::FIELD_LABELS[$field] ?? $field;
                $context->buildViolation("Le champ « {$fieldLabel} » est requis pour le {$typeLabel}.")
                    ->atPath("details.transport.{$field}")
                    ->addViolation();
            }
        }
    }

    private function validateRequiredAttachments(Collection $attachments, array $transport, string $type, ExecutionContextInterface $context): void
    {
        $requiredAttachments = $this->getRequiredAttachmentsForTransport($transport, $type);

        $this->attachmentValidator->validate($attachments, $requiredAttachments, $context);
    }

    private function getRequiredAttachmentsForTransport(array $transport, string $type): array
    {
        $attachmentFields = self::REQUIRED_ATTACHMENTS[$type] ?? [];
        $requiredAttachments = [];

        foreach ($attachmentFields as $expenseId) {
            if ($this->hasStrictlyPositiveAmount($transport, $expenseId)) {
                $requiredAttachments[] = $expenseId;
            }
        }

        return $requiredAttachments;
    }

    private function hasStrictlyPositiveAmount(array $transport, string $expenseId): bool
    {
        return $this->isStrictlyPositiveAmount($transport[$expenseId] ?? null);
    }

    private function isStrictlyPositiveAmount(mixed $value): bool
    {
        return \is_numeric($value) && (float) $value > 0;
    }
}
