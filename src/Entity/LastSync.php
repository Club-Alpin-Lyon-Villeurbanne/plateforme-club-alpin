<?php

namespace App\Entity;

use App\Repository\LastSyncRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'caf_last_sync')]
#[ORM\Entity(repositoryClass: LastSyncRepository::class)]
class LastSync
{
    #[ORM\Id]
    #[ORM\Column(name: 'type', type: Types::STRING, length: 50, nullable: false)]
    private string $type;

    #[ORM\Column(name: 'last_sync', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastSyncDate;

    #[ORM\Column(name: 'records_count', type: Types::INTEGER, nullable: false, options: ['default' => 0])]
    private ?int $recordsCount;

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getLastSyncDate(): ?\DateTimeImmutable
    {
        return $this->lastSyncDate;
    }

    public function setLastSyncDate(?\DateTimeImmutable $lastSyncDate): self
    {
        $this->lastSyncDate = $lastSyncDate;

        return $this;
    }

    public function getRecordsCount(): ?int
    {
        return $this->recordsCount;
    }

    public function setRecordsCount(?int $recordsCount): self
    {
        $this->recordsCount = $recordsCount;

        return $this;
    }
}
