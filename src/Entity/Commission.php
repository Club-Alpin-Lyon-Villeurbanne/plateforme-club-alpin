<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\CommissionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Commission.
 */
#[ORM\Table(name: 'caf_commission')]
#[ORM\Entity(repositoryClass: CommissionRepository::class)]
#[ApiResource(
    operations: [new Get(), new GetCollection()],
    normalizationContext: ['groups' => ['commission:read']],
    order: ['ordre' => 'ASC'],
    security: "is_granted('ROLE_USER')",
)]
class Commission
{
    public const array CONFIGURABLE_FIELDS = ['difficulte', 'distance', 'denivele'];

    /**
     * @var int
     */
    #[ORM\Column(name: 'id_commission', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['event:read', 'article:read', 'commission:read'])]
    private $id;

    /**
     * @var int
     */
    #[ORM\Column(name: 'ordre_commission', type: 'integer', nullable: false)]
    #[Groups('commission:read')]
    private $ordre;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'vis_commission', type: 'boolean', nullable: false)]
    private $vis = true;

    /**
     * @var string
     */
    #[ORM\Column(name: 'code_commission', type: 'string', length: 50, nullable: false, unique: true)]
    #[Groups('commission:read')]
    private $code;

    /**
     * @var string
     */
    #[ORM\Column(name: 'title_commission', type: 'string', length: 30, nullable: false)]
    #[Groups(['commission:read', 'event:read'])]
    private $title;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 30, nullable: true)]
    #[Groups('commission:read')]
    private $googleDriveId;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $mandatoryFields = self::CONFIGURABLE_FIELDS;

    /** @var Collection<int, FormationReferentielBrevet> */
    #[ORM\ManyToMany(targetEntity: FormationReferentielBrevet::class)]
    #[ORM\JoinTable(name: 'formation_commission_brevet')]
    #[ORM\JoinColumn(name: 'commission_id', referencedColumnName: 'id_commission', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'brevet_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\OrderBy(['codeBrevet' => 'DESC'])]
    private Collection $brevets;

    /** @var Collection<int, FormationReferentielFormation> */
    #[ORM\ManyToMany(targetEntity: FormationReferentielFormation::class)]
    #[ORM\JoinTable(name: 'formation_commission_formation')]
    #[ORM\JoinColumn(name: 'commission_id', referencedColumnName: 'id_commission', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'formation_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\OrderBy(['codeFormation' => 'ASC'])]
    private Collection $formations;

    /** @var Collection<int, FormationReferentielGroupeCompetence> */
    #[ORM\ManyToMany(targetEntity: FormationReferentielGroupeCompetence::class)]
    #[ORM\JoinTable(name: 'formation_commission_groupe_competence')]
    #[ORM\JoinColumn(name: 'commission_id', referencedColumnName: 'id_commission', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'groupe_competence_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\OrderBy(['intitule' => 'ASC'])]
    private Collection $groupesCompetences;

    /** @var Collection<int, FormationReferentielNiveauPratique> */
    #[ORM\ManyToMany(targetEntity: FormationReferentielNiveauPratique::class)]
    #[ORM\JoinTable(name: 'formation_commission_niveau_pratique')]
    #[ORM\JoinColumn(name: 'commission_id', referencedColumnName: 'id_commission', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'niveau_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\OrderBy(['libelle' => 'DESC'])]
    private Collection $niveaux;

    public function __construct(string $title, string $code, int $ordre)
    {
        $this->title = $title;
        $this->code = $code;
        $this->ordre = $ordre;
        $this->brevets = new ArrayCollection();
        $this->formations = new ArrayCollection();
        $this->groupesCompetences = new ArrayCollection();
        $this->niveaux = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->title;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): self
    {
        $this->ordre = $ordre;

        return $this;
    }

    public function getVis(): ?bool
    {
        return $this->vis;
    }

    public function setVis(bool $vis): self
    {
        $this->vis = $vis;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getGoogleDriveId(): ?string
    {
        return $this->googleDriveId;
    }

    public function setGoogleDriveId(string $googleDriveId): self
    {
        $this->googleDriveId = $googleDriveId;

        return $this;
    }

    public function getMandatoryFields(): array
    {
        return $this->mandatoryFields;
    }

    public function setMandatoryFields(array $mandatoryFields): self
    {
        $this->mandatoryFields = $mandatoryFields;

        return $this;
    }

    /** @return Collection<int, FormationReferentielBrevet> */
    public function getBrevets(): Collection
    {
        return $this->brevets;
    }

    public function hasBrevets(): bool
    {
        return count($this->brevets) > 0;
    }

    public function addBrevet(FormationReferentielBrevet $brevet): self
    {
        if (!$this->brevets->contains($brevet)) {
            $this->brevets->add($brevet);
        }

        return $this;
    }

    public function removeBrevet(FormationReferentielBrevet $brevet): self
    {
        $this->brevets->removeElement($brevet);

        return $this;
    }

    /** @return Collection<int, FormationReferentielFormation> */
    public function getFormations(): Collection
    {
        return $this->formations;
    }

    public function hasFormations(): bool
    {
        return count($this->formations) > 0;
    }

    public function addFormation(FormationReferentielFormation $formation): self
    {
        if (!$this->formations->contains($formation)) {
            $this->formations->add($formation);
        }

        return $this;
    }

    public function removeFormation(FormationReferentielFormation $formation): self
    {
        $this->formations->removeElement($formation);

        return $this;
    }

    /** @return Collection<int, FormationReferentielGroupeCompetence> */
    public function getGroupesCompetences(): Collection
    {
        return $this->groupesCompetences;
    }

    public function hasGroupesCompetences(): bool
    {
        return count($this->groupesCompetences) > 0;
    }

    public function addGroupeCompetence(FormationReferentielGroupeCompetence $groupe): self
    {
        if (!$this->groupesCompetences->contains($groupe)) {
            $this->groupesCompetences->add($groupe);
        }

        return $this;
    }

    public function removeGroupeCompetence(FormationReferentielGroupeCompetence $groupe): self
    {
        $this->groupesCompetences->removeElement($groupe);

        return $this;
    }

    /** @return Collection<int, FormationReferentielNiveauPratique> */
    public function getNiveaux(): Collection
    {
        return $this->niveaux;
    }

    public function hasNiveaux(): bool
    {
        return count($this->niveaux) > 0;
    }

    public function addNiveau(FormationReferentielNiveauPratique $niveau): self
    {
        if (!$this->niveaux->contains($niveau)) {
            $this->niveaux->add($niveau);
        }

        return $this;
    }

    public function removeNiveau(FormationReferentielNiveauPratique $niveau): self
    {
        $this->niveaux->removeElement($niveau);

        return $this;
    }
}
