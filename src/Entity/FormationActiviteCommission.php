<?php

namespace App\Entity;

use App\Repository\FormationActiviteCommissionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'formation_activite_commission')]
#[ORM\Index(columns: ['commission_id'], name: 'IDX_FORM_ACT_COMM_COMMISSION')]
#[ORM\UniqueConstraint(name: 'UNIQ_FORM_ACT_COMM_CODE', columns: ['code_activite'])]
#[ORM\Entity(repositoryClass: FormationActiviteCommissionRepository::class)]
class FormationActiviteCommission
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 10, nullable: false)]
    private string $codeActivite;

    #[ORM\ManyToOne(targetEntity: Commission::class)]
    #[ORM\JoinColumn(name: 'commission_id', referencedColumnName: 'id_commission', nullable: false, onDelete: 'CASCADE')]
    private Commission $commission;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodeActivite(): string
    {
        return $this->codeActivite;
    }

    public function setCodeActivite(string $codeActivite): self
    {
        $this->codeActivite = $codeActivite;

        return $this;
    }

    public function getCommission(): Commission
    {
        return $this->commission;
    }

    public function setCommission(Commission $commission): self
    {
        $this->commission = $commission;

        return $this;
    }
}
