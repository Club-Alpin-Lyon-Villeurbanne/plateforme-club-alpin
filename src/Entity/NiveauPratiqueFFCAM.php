<?php

namespace App\Entity;

use App\Repository\NiveauPratiqueFFCAMRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NiveauPratiqueFFCAMRepository::class)]
#[ORM\Table(name: 'caf_niveau_pratique_ffcam')]
#[ORM\Index(name: 'idx_niveau_pratique_adherent', columns: ['adherent_id'])]
#[ORM\Index(name: 'idx_niveau_pratique_activite', columns: ['activite'])]
#[ORM\Index(name: 'idx_niveau_pratique_niveau', columns: ['niveau_pratique'])]
#[ORM\HasLifecycleCallbacks]
class NiveauPratiqueFFCAM
{
    public const NIVEAU_INITIE = 'INITIE';
    public const NIVEAU_PERFECTIONNE = 'PERFECTIONNE';
    public const NIVEAU_SPECIALISE = 'SPECIALISE';

    public const ACTIVITE_ESCALADE = 'ESCALADE';
    public const ACTIVITE_SPORTS_DE_NEIGE = 'SPORTS DE NEIGE';
    public const ACTIVITE_RANDONNEE = 'RANDONNEE';
    public const ACTIVITE_ALPINISME = 'ALPINISME';
    public const ACTIVITE_DESCENTE_CANYON = 'DESCENTE DE CANYON';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'adherent_id', referencedColumnName: 'id_user', nullable: false)]
    private ?User $adherent = null;

    #[ORM\Column(length: 100)]
    private ?string $activite = null;

    #[ORM\Column(name: 'nom_activite', type: Types::TEXT)]
    private ?string $nomActivite = null;

    #[ORM\Column(name: 'niveau_pratique', length: 50)]
    private ?string $niveauPratique = null;

    #[ORM\Column(name: 'date_validation', type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateValidation = null;

    #[ORM\Column(length: 10)]
    private ?string $club = null;

    #[ORM\Column(length: 5)]
    private ?string $territoire = null;

    #[ORM\Column(name: 'date_creation', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(name: 'date_modification', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateModification = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAdherent(): ?User
    {
        return $this->adherent;
    }

    public function setAdherent(?User $adherent): static
    {
        $this->adherent = $adherent;

        return $this;
    }

    public function getActivite(): ?string
    {
        return $this->activite;
    }

    public function setActivite(string $activite): static
    {
        $this->activite = $activite;

        return $this;
    }

    public function getNomActivite(): ?string
    {
        return $this->nomActivite;
    }

    public function setNomActivite(string $nomActivite): static
    {
        $this->nomActivite = $nomActivite;

        return $this;
    }

    public function getNiveauPratique(): ?string
    {
        return $this->niveauPratique;
    }

    public function setNiveauPratique(string $niveauPratique): static
    {
        $this->niveauPratique = $niveauPratique;

        return $this;
    }

    public function getDateValidation(): ?\DateTimeInterface
    {
        return $this->dateValidation;
    }

    public function setDateValidation(\DateTimeInterface $dateValidation): static
    {
        $this->dateValidation = $dateValidation;

        return $this;
    }

    public function getClub(): ?string
    {
        return $this->club;
    }

    public function setClub(string $club): static
    {
        $this->club = $club;

        return $this;
    }

    public function getTerritoire(): ?string
    {
        return $this->territoire;
    }

    public function setTerritoire(string $territoire): static
    {
        $this->territoire = $territoire;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getDateModification(): ?\DateTimeInterface
    {
        return $this->dateModification;
    }

    public function setDateModification(\DateTimeInterface $dateModification): static
    {
        $this->dateModification = $dateModification;

        return $this;
    }

    #[ORM\PrePersist]
    public function setDateCreationValue(): void
    {
        if ($this->dateCreation === null) {
            $this->dateCreation = new \DateTime();
        }
        $this->setDateModificationValue();
    }

    #[ORM\PreUpdate]
    public function setDateModificationValue(): void
    {
        $this->dateModification = new \DateTime();
    }

    /**
     * Extrait le niveau de pratique du nom de l'activité (INITIE, PERFECTIONNE, SPECIALISE)
     */
    public function extraireNiveauPratiqueDepuisNomActivite(): ?string
    {
        if (preg_match('/^(INITIE|PERFECTIONNE|SPECIALISE)\s+/', $this->nomActivite, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Retourne les niveaux de pratique possibles
     */
    public static function getNiveauxPratique(): array
    {
        return [
            self::NIVEAU_INITIE,
            self::NIVEAU_PERFECTIONNE,
            self::NIVEAU_SPECIALISE,
        ];
    }

    /**
     * Retourne les activités possibles
     */
    public static function getActivites(): array
    {
        return [
            self::ACTIVITE_ESCALADE,
            self::ACTIVITE_SPORTS_DE_NEIGE,
            self::ACTIVITE_RANDONNEE,
            self::ACTIVITE_ALPINISME,
            self::ACTIVITE_DESCENTE_CANYON,
        ];
    }
}