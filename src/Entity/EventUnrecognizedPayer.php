<?php

namespace App\Entity;

use App\Repository\EventUnrecognizedPayerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'event_unrecognized_payer')]
#[ORM\Index(columns: ['id'], name: 'id')]
#[ORM\Entity(repositoryClass: EventUnrecognizedPayerRepository::class)]
class EventUnrecognizedPayer
{
    #[ORM\Column(name: 'id', type: Types::INTEGER, nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: 'Evt', fetch: 'EAGER', inversedBy: 'unrecognizedPayers')]
    #[ORM\JoinColumn(name: 'event_id', referencedColumnName: 'id_evt', nullable: false, onDelete: 'CASCADE')]
    private Evt $event;

    #[ORM\Column(name: 'payer_email', type: Types::STRING, nullable: false)]
    private string $email;

    #[ORM\Column(name: 'payer_lastname', type: Types::STRING, nullable: false)]
    private string $lastname;

    #[ORM\Column(name: 'payer_firstname', type: Types::STRING, nullable: false)]
    private string $firstname;

    #[ORM\Column(name: 'has_paid', type: Types::BOOLEAN, nullable: false, options: ['default' => false])]
    private bool $hasPaid = false;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getEvent(): Evt
    {
        return $this->event;
    }

    public function setEvent(Evt $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $payerEmail): self
    {
        $this->email = $payerEmail;

        return $this;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function hasPaid(): bool
    {
        return $this->hasPaid;
    }

    public function setHasPaid(bool $hasPaid): self
    {
        $this->hasPaid = $hasPaid;

        return $this;
    }
}
