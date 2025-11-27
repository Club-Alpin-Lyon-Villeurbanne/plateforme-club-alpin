<?php

namespace App\Entity;

use App\Repository\BrevetReferentielRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'formation_brevet_referentiel')]
#[ORM\UniqueConstraint(name: 'UNIQ_CODE_BREVET', columns: ['code_brevet'])]
#[ORM\Entity(repositoryClass: BrevetReferentielRepository::class)]
class BrevetReferentiel
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[ORM\Column(name: 'code_brevet', type: Types::STRING, length: 50, nullable: false)]
    private string $codeBrevet;

    #[ORM\Column(name: 'intitule', type: Types::STRING, length: 255)]
    private string $intitule;

    /** @var Collection<int, Commission> */
    #[ORM\ManyToMany(targetEntity: Commission::class)]
    #[ORM\JoinTable(name: 'formation_brevet_commission')]
    #[ORM\JoinColumn(name: 'brevet_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'commission_id', referencedColumnName: 'id_commission', onDelete: 'CASCADE')]
    private Collection $commissions;

    public function __construct()
    {
        $this->commissions = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getCodeBrevet(): string
    {
        return $this->codeBrevet;
    }

    public function setCodeBrevet(string $codeBrevet): self
    {
        $this->codeBrevet = $codeBrevet;

        return $this;
    }

    public function getIntitule(): string
    {
        return $this->intitule;
    }

    public function setIntitule(string $intitule): self
    {
        $this->intitule = $intitule;

        return $this;
    }

    /** @return Collection<int, Commission> */
    public function getCommissions(): Collection
    {
        return $this->commissions;
    }

    public function addCommission(Commission $commission): self
    {
        if (!$this->commissions->contains($commission)) {
            $this->commissions->add($commission);
        }

        return $this;
    }

    public function removeCommission(Commission $commission): self
    {
        $this->commissions->removeElement($commission);

        return $this;
    }
}
