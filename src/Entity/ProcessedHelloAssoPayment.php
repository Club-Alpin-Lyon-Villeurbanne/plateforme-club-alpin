<?php

namespace App\Entity;

use App\Repository\ProcessedHelloAssoPaymentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'processed_hello_asso_payment')]
#[ORM\UniqueConstraint(name: 'UNIQ_hello_asso_payment_id', columns: ['hello_asso_payment_id'])]
#[ORM\Entity(repositoryClass: ProcessedHelloAssoPaymentRepository::class)]
class ProcessedHelloAssoPayment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 64)]
    private string $helloAssoPaymentId;

    #[ORM\Column(type: Types::INTEGER)]
    private int $reservationId;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $processedAt;

    public function __construct(string $helloAssoPaymentId, int $reservationId)
    {
        $this->helloAssoPaymentId = $helloAssoPaymentId;
        $this->reservationId = $reservationId;
        $this->processedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHelloAssoPaymentId(): string
    {
        return $this->helloAssoPaymentId;
    }

    public function getReservationId(): int
    {
        return $this->reservationId;
    }

    public function getProcessedAt(): \DateTimeImmutable
    {
        return $this->processedAt;
    }
}
