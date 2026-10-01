<?php

namespace App\Utils\Enums;

enum ExpenseReportStatusEnum: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case REJECTED = 'rejected';
    case APPROVED = 'approved';
    case ACCOUNTED = 'accounted';

    /**
     * Le contenu de la note (montants, type de demande, justificatifs) n'est modifiable
     * par son propriétaire que dans ces statuts.
     */
    public function isEditableByOwner(): bool
    {
        return self::DRAFT === $this || self::REJECTED === $this;
    }
}
