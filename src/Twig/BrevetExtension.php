<?php

namespace App\Twig;

use App\Entity\BrevetReferentiel;
use App\Entity\Commission;
use App\Entity\FormationCompetenceReferentiel;
use App\Entity\FormationNiveauReferentiel;
use App\Entity\User;
use App\Repository\BrevetAdherentRepository;
use App\Repository\FormationCompetenceValidationRepository;
use App\Repository\FormationNiveauValidationRepository;
use App\Repository\FormationValidationRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class BrevetExtension extends AbstractExtension
{
    public function __construct(
        protected BrevetAdherentRepository $brevetAdherentRepository,
        protected FormationValidationRepository $formationValidationRepository,
        protected FormationNiveauValidationRepository $formationNiveauValidationRepository,
        protected FormationCompetenceValidationRepository $formationCompetenceValidationRepository,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('date_brevet_user', [$this, 'getDateUserBrevet']),
            new TwigFunction('date_formation_user', [$this, 'getDateUserFormation']),
            new TwigFunction('date_niveau_user', [$this, 'getDateUserNiveau']),
            new TwigFunction('date_competence_user', [$this, 'getDateUserGroupeCompetence']),
        ];
    }

    public function getDateUserBrevet(User $user, BrevetReferentiel $brevet): ?string
    {
        $userBrevet = $this->brevetAdherentRepository->getDateByUserAndBrevet($user, $brevet);

        if (null === $userBrevet) {
            return null;
        }

        $date = $userBrevet->getDateObtention();
        if (!empty($userBrevet->getDateRecyclage())) {
            $date = $userBrevet->getDateRecyclage();
        }

        return $date->format('d/m/Y');
    }

    public function getDateUserFormation(User $user, string $formationCode): ?string
    {
        $userFormation = $this->formationValidationRepository->findOneBy(['user' => $user, 'formation' => $formationCode]);

        if (null === $userFormation) {
            return null;
        }

        return $userFormation->getDateValidation()->format('d/m/Y');
    }

    public function getDateUserNiveau(User $user, FormationNiveauReferentiel $niveau, ?Commission $commission = null): ?string
    {
        $userNiveau = $this->formationNiveauValidationRepository->findOneBy(['user' => $user, 'niveauReferentiel' => $niveau]);

        if (null === $userNiveau) {
            return null;
        }
        if (!empty($commission)) {
            if ($commission->getCodeFfcamNiveau() !== $niveau->getCodeActivite()) {
                return null;
            }
        }

        return $userNiveau->getDateValidation()->format('d/m/Y');
    }

    public function getDateUserGroupeCompetence(User $user, FormationCompetenceReferentiel $groupe, ?Commission $commission = null): ?string
    {
        $userGroupeComp = $this->formationCompetenceValidationRepository->findOneBy(['user' => $user, 'competence' => $groupe]);

        if (null === $userGroupeComp) {
            return null;
        }
        if (!empty($commission)) {
            if ($commission->getCodeFfcamGroupeCompetence() !== $groupe->getCodeActivite()) {
                return null;
            }
        }

        return $userGroupeComp->getDateValidation()->format('d/m/Y');
    }
}
