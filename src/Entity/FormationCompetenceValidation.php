<?php

namespace App\Entity;

use App\Repository\FormationCompetenceValidationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'formation_competence_validation')]
#[ORM\Index(columns: ['user_id'], name: 'IDX_FORM_COMP_VAL_USER')]
#[ORM\Index(columns: ['competence_id'], name: 'IDX_FORM_COMP_VAL_COMP')]
#[ORM\Index(columns: ['date_validation'], name: 'IDX_FORM_COMP_VAL_DATE')]
#[ORM\Index(columns: ['est_valide'], name: 'IDX_FORM_COMP_VAL_VALID')]
#[ORM\UniqueConstraint(name: 'UNIQ_COMP_VAL_USER_COMP', columns: ['user_id', 'competence_id'])]
#[ORM\Entity(repositoryClass: FormationCompetenceValidationRepository::class)]
class FormationCompetenceValidation
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id_user', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: FormationCompetenceReferentiel::class)]
    #[ORM\JoinColumn(name: 'competence_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private FormationCompetenceReferentiel $competence;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $niveauAssocie = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateValidation = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false, options: ['default' => 0])]
    private bool $estValide = false;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $validePar = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

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

    public function getCompetence(): FormationCompetenceReferentiel
    {
        return $this->competence;
    }

    public function setCompetence(FormationCompetenceReferentiel $competence): self
    {
        $this->competence = $competence;

        return $this;
    }

    public function getNiveauAssocie(): ?string
    {
        return $this->niveauAssocie;
    }

    public function setNiveauAssocie(?string $niveauAssocie): self
    {
        $this->niveauAssocie = $niveauAssocie;

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

    public function isEstValide(): bool
    {
        return $this->estValide;
    }

    public function setEstValide(bool $estValide): self
    {
        $this->estValide = $estValide;

        return $this;
    }

    public function getValidePar(): ?string
    {
        return $this->validePar;
    }

    public function setValidePar(?string $validePar): self
    {
        $this->validePar = $validePar;

        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): self
    {
        $this->commentaire = $commentaire;

        return $this;
    }
}
