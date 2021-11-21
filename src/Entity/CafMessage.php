<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CafMessage.
 *
 * @ORM\Table(name="caf_message")
 * @ORM\Entity
 */
class CafMessage
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_message", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idMessage;

    /**
     * @var int
     *
     * @ORM\Column(name="date_message", type="bigint", nullable=false)
     */
    private $dateMessage;

    /**
     * @var string
     *
     * @ORM\Column(name="to_message", type="string", length=100, nullable=false)
     */
    private $toMessage;

    /**
     * @var string
     *
     * @ORM\Column(name="from_message", type="string", length=100, nullable=false)
     */
    private $fromMessage;

    /**
     * @var string
     *
     * @ORM\Column(name="headers_message", type="string", length=500, nullable=false)
     */
    private $headersMessage;

    /**
     * @var string
     *
     * @ORM\Column(name="code_message", type="string", length=30, nullable=false)
     */
    private $codeMessage;

    /**
     * @var string
     *
     * @ORM\Column(name="cont_message", type="text", length=65535, nullable=false)
     */
    private $contMessage;

    /**
     * @var bool
     *
     * @ORM\Column(name="success_message", type="boolean", nullable=false)
     */
    private $successMessage;

    public function getIdMessage(): ?int
    {
        return $this->idMessage;
    }

    public function getDateMessage(): ?string
    {
        return $this->dateMessage;
    }

    public function setDateMessage(string $dateMessage): self
    {
        $this->dateMessage = $dateMessage;

        return $this;
    }

    public function getToMessage(): ?string
    {
        return $this->toMessage;
    }

    public function setToMessage(string $toMessage): self
    {
        $this->toMessage = $toMessage;

        return $this;
    }

    public function getFromMessage(): ?string
    {
        return $this->fromMessage;
    }

    public function setFromMessage(string $fromMessage): self
    {
        $this->fromMessage = $fromMessage;

        return $this;
    }

    public function getHeadersMessage(): ?string
    {
        return $this->headersMessage;
    }

    public function setHeadersMessage(string $headersMessage): self
    {
        $this->headersMessage = $headersMessage;

        return $this;
    }

    public function getCodeMessage(): ?string
    {
        return $this->codeMessage;
    }

    public function setCodeMessage(string $codeMessage): self
    {
        $this->codeMessage = $codeMessage;

        return $this;
    }

    public function getContMessage(): ?string
    {
        return $this->contMessage;
    }

    public function setContMessage(string $contMessage): self
    {
        $this->contMessage = $contMessage;

        return $this;
    }

    public function getSuccessMessage(): ?bool
    {
        return $this->successMessage;
    }

    public function setSuccessMessage(bool $successMessage): self
    {
        $this->successMessage = $successMessage;

        return $this;
    }
}
