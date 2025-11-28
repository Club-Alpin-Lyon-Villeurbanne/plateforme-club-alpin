<?php

namespace App\Entity;

use App\Repository\FormationValidationNiveauPratiqueRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'formation_validation_niveau_pratique')]
#[ORM\Index(columns: ['user_id'], name: 'IDX_FORM_NIV_VAL_USER')]
#[ORM\Index(columns: ['cursus_niveau_id'], name: 'IDX_FORM_NIV_VAL_CURSUS')]
#[ORM\Index(columns: ['date_validation'], name: 'IDX_FORM_NIV_VAL_DATE')]
#[ORM\UniqueConstraint(name: 'UNIQ_FORM_USER_NIV', columns: ['user_id', 'cursus_niveau_id'])]
#[ORM\Entity(repositoryClass: FormationValidationNiveauPratiqueRepository::class)]
class FormationValidationNiveauPratique
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id_user', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: FormationReferentielNiveauPratique::class)]
    #[ORM\JoinColumn(name: 'cursus_niveau_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private FormationReferentielNiveauPratique $niveauReferentiel;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateValidation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getNiveauReferentiel(): FormationReferentielNiveauPratique
    {
        return $this->niveauReferentiel;
    }

    public function setNiveauReferentiel(FormationReferentielNiveauPratique $niveauReferentiel): self
    {
        $this->niveauReferentiel = $niveauReferentiel;

        return $this;
    }

    public function getDateValidation(): ?\DateTimeInterface
    {
        return $this->dateValidation;
    }

    public function setDateValidation(?\DateTimeInterface $dateValidation): self
    {
        $this->dateValidation = $dateValidation;

        return $this;
    }
}
