<?php

namespace App\Twig;

use App\Entity\Commission;
use App\Entity\FormationReferentielBrevet;
use App\Entity\FormationReferentielFormation;
use App\Entity\FormationReferentielGroupeCompetence;
use App\Entity\FormationReferentielNiveauPratique;
use App\Entity\FormationValidationBrevet;
use App\Entity\FormationValidationFormation;
use App\Entity\FormationValidationGroupeCompetence;
use App\Entity\FormationValidationNiveauPratique;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class BrevetExtension extends AbstractExtension
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('intitule_brevet', [$this, 'getIntituleBrevet']),
            new TwigFunction('intitule_formation', [$this, 'getIntituleFormation']),
            new TwigFunction('date_brevet_user', [$this, 'getDateUserBrevet']),
            new TwigFunction('date_formation_user', [$this, 'getDateUserFormation']),
            new TwigFunction('date_niveau_user', [$this, 'getDateUserNiveau']),
            new TwigFunction('date_competence_user', [$this, 'getDateUserGroupeCompetence']),
        ];
    }

    public function getDateUserBrevet(User $user, string $code): ?string
    {
        $entity = $this->entityManager->getRepository(FormationReferentielBrevet::class)->findOneBy(['codeBrevet' => $code]);
        $userBrevet = $this->entityManager->getRepository(FormationValidationBrevet::class)->findOneBy(['user' => $user, 'brevet' => $entity]);

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
        $formation = $this->entityManager->getRepository(FormationReferentielFormation::class)->findOneBy(['codeFormation' => $formationCode]);
        $userFormation = $this->entityManager->getRepository(FormationValidationFormation::class)->findOneBy(['user' => $user, 'formation' => $formation]);

        return $userFormation?->getDateValidation()->format('d/m/Y');
    }

    public function getDateUserNiveau(User $user, FormationReferentielNiveauPratique $niveau, ?Commission $commission = null): ?string
    {
        $userNiveau = $this->entityManager->getRepository(FormationValidationNiveauPratique::class)->findOneBy(['user' => $user, 'niveauReferentiel' => $niveau]);

        if (null === $userNiveau) {
            return null;
        }
        if (!empty($commission)) {
            if (!in_array($niveau, $commission->getNiveaux()->toArray(), true)) {
                return null;
            }
        }

        return $userNiveau?->getDateValidation()->format('d/m/Y');
    }

    public function getDateUserGroupeCompetence(User $user, FormationReferentielGroupeCompetence $groupe, ?Commission $commission = null): ?string
    {
        $userGroupeComp = $this->entityManager->getRepository(FormationValidationGroupeCompetence::class)->findOneBy(['user' => $user, 'competence' => $groupe]);

        if (null === $userGroupeComp) {
            return null;
        }
        if (!empty($commission)) {
            if (!in_array($groupe, $commission->getGroupesCompetences()->toArray(), true)) {
                return null;
            }
        }

        return $userGroupeComp->getDateValidation()->format('d/m/Y');
    }

    public function getIntituleBrevet(string $code): string
    {
        $entity = $this->entityManager->getRepository(FormationReferentielBrevet::class)->findOneBy(['codeBrevet' => $code]);
        if (!$entity instanceof FormationReferentielBrevet) {
            return '';
        }

        return $entity->getIntitule();
    }

    public function getIntituleFormation(string $code): string
    {
        $entity = $this->entityManager->getRepository(FormationReferentielFormation::class)->findOneBy(['codeFormation' => $code]);
        if (!$entity instanceof FormationReferentielFormation) {
            return '';
        }

        return $entity->getIntitule();
    }
}
