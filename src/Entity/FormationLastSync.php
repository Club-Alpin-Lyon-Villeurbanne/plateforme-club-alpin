<?php

namespace App\Entity;

use App\Repository\FormationLastSyncRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'formation_last_sync')]
#[ORM\Entity(repositoryClass: FormationLastSyncRepository::class)]
class FormationLastSync
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $type;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastSync = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $recordsCount = 0;

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getLastSync(): ?\DateTimeInterface
    {
        return $this->lastSync;
    }

    public function setLastSync(?\DateTimeInterface $lastSync): self
    {
        $this->lastSync = $lastSync;

        return $this;
    }

    public function getRecordsCount(): int
    {
        return $this->recordsCount;
    }

    public function setRecordsCount(int $recordsCount): self
    {
        $this->recordsCount = $recordsCount;

        return $this;
    }
}
