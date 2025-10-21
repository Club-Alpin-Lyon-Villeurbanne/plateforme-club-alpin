<?php

namespace App\Entity;

use App\Repository\BrevetAdherentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'formation_brevet')]
#[ORM\Index(columns: ['cafnum_user'], name: 'idx_cafnum')]
#[ORM\Index(columns: ['code_brevet'], name: 'idx_code_brevet')]
#[ORM\Index(columns: ['date_obtention'], name: 'idx_date_obtention')]
#[ORM\Entity(repositoryClass: BrevetAdherentRepository::class)]
class BrevetAdherent
{
    use TimestampableEntity;

    #[ORM\Column(name: 'id', type: Types::INTEGER, nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id_user', nullable: false)]
    private ?User $user;

    #[ORM\Column(name: 'cafnum_user', type: Types::STRING, length: 20, nullable: false)]
    private string $cafnum;

    #[ORM\ManyToOne(targetEntity: BrevetReferentiel::class)]
    #[ORM\JoinColumn(name: 'code_brevet', referencedColumnName: 'code_brevet', nullable: false)]
    private BrevetReferentiel $brevet;

    #[ORM\Column(name: 'date_obtention', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateObtention;

    #[ORM\Column(name: 'date_recyclage', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateRecyclage;

    #[ORM\Column(name: 'date_edition', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateEdition;

    #[ORM\Column(name: 'date_formation_continue', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateFormationContinue;

    #[ORM\Column(name: 'date_migration', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateMigration;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getCafnum(): string
    {
        return $this->cafnum;
    }

    public function setCafnum(string $cafnum): self
    {
        $this->cafnum = $cafnum;

        return $this;
    }

    public function getBrevet(): BrevetReferentiel
    {
        return $this->brevet;
    }

    public function setBrevet(BrevetReferentiel $brevet): self
    {
        $this->brevet = $brevet;

        return $this;
    }

    public function getDateObtention(): ?\DateTimeImmutable
    {
        return $this->dateObtention;
    }

    public function setDateObtention(?\DateTimeImmutable $dateObtention): self
    {
        $this->dateObtention = $dateObtention;

        return $this;
    }

    public function getDateRecyclage(): ?\DateTimeImmutable
    {
        return $this->dateRecyclage;
    }

    public function setDateRecyclage(?\DateTimeImmutable $dateRecyclage): self
    {
        $this->dateRecyclage = $dateRecyclage;

        return $this;
    }

    public function getDateEdition(): ?\DateTimeImmutable
    {
        return $this->dateEdition;
    }

    public function setDateEdition(?\DateTimeImmutable $dateEdition): self
    {
        $this->dateEdition = $dateEdition;

        return $this;
    }

    public function getDateFormationContinue(): ?\DateTimeImmutable
    {
        return $this->dateFormationContinue;
    }

    public function setDateFormationContinue(?\DateTimeImmutable $dateFormationContinue): self
    {
        $this->dateFormationContinue = $dateFormationContinue;

        return $this;
    }

    public function getDateMigration(): ?\DateTimeImmutable
    {
        return $this->dateMigration;
    }

    public function setDateMigration(?\DateTimeImmutable $dateMigration): self
    {
        $this->dateMigration = $dateMigration;

        return $this;
    }
}
