<?php

namespace App\Twig;

use App\Entity\FormationReferentielBrevet;
use App\Entity\FormationReferentielFormation;
use App\Entity\FormationReferentielNiveauPratique;
use App\Entity\FormationValidationBrevet;
use App\Entity\FormationValidationFormation;
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
            new TwigFunction('date_brevet_user', [$this, 'getDateUserBrevet']),
            new TwigFunction('date_formation_user', [$this, 'getDateUserFormation']),
            new TwigFunction('date_niveau_user', [$this, 'getDateUserNiveau']),
        ];
    }

    public function getDateUserBrevet(User $user, FormationReferentielBrevet $brevet): ?string
    {
        $userBrevet = $this->entityManager->getRepository(FormationValidationBrevet::class)->findOneBy(['user' => $user, 'brevet' => $brevet]);

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

    public function getDateUserNiveau(User $user, FormationReferentielNiveauPratique $niveau): ?string
    {
        $userNiveau = $this->entityManager->getRepository(FormationValidationNiveauPratique::class)->findOneBy(['user' => $user, 'niveauReferentiel' => $niveau]);

        return $userNiveau?->getDateValidation()->format('d/m/Y');
    }
}
