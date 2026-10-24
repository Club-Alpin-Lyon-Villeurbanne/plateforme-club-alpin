<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Une note de sortie se crée par sa sortie (eventId) ; une note hors sortie par son type,
 * sa commission, son objet et sa date.
 */
class ExpenseReportCreateDto
{
    #[Assert\When(expression: 'this.type === null', constraints: [new Assert\NotBlank()])]
    #[Assert\When(expression: 'this.type !== null', constraints: [new Assert\IsNull(message: 'Une note hors sortie n\'est pas rattachée à une sortie.')])]
    #[Assert\Positive]
    public $eventId;

    #[Assert\Choice(choices: ['formation', 'autre'], message: 'Le type de note doit être « formation » ou « autre ».')]
    public ?string $type = null;

    public ?int $commissionId = null;

    public ?string $objet = null;

    #[Assert\Date(message: 'La date doit être au format AAAA-MM-JJ.')]
    public ?string $dateDepense = null;

    public ?string $lienFormation = null;
}
