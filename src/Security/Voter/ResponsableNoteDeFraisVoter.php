<?php

namespace App\Security\Voter;

use App\Entity\ExpenseReport;
use App\Entity\User;
use App\Service\NoteDeFrais\ValideursNoteHorsSortie;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Le responsable d'une note hors sortie : l'un de ses valideurs. Il lit la note et ses
 * justificatifs (ACCESS_EXPENSE_REPORT, qu'ExpenseReportVoter accorde au propriétaire et au
 * gestionnaire), et la valide ou la renvoie.
 */
class ResponsableNoteDeFraisVoter extends Voter
{
    public const RESPONSABLE_DE_LA_NOTE = 'responsable_note_de_frais';

    public function __construct(
        private readonly ValideursNoteHorsSortie $valideurs,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::RESPONSABLE_DE_LA_NOTE, ExpenseReportVoter::ACCESS_EXPENSE_REPORT], true)
            && $subject instanceof ExpenseReport;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $utilisateur = $token->getUser();

        return $utilisateur instanceof User && $this->valideurs->estValideur($utilisateur, $subject);
    }
}
