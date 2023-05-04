<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Message.
 *
 * @ORM\Table(name="caf_message")
 *
 * @ORM\Entity
 */
class Message
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_message", type="integer", nullable=false)
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="date_message", type="bigint", nullable=false)
     */
    private $date;

    /**
     * @var string
     *
     * @ORM\Column(name="to_message", type="string", length=100, nullable=false, options={"collation": "utf8mb4_unicode_ci"})
     */
    private $to;

    /**
     * @var string
     *
     * @ORM\Column(name="from_message", type="string", length=100, nullable=false, options={"collation": "utf8mb4_unicode_ci"})
     */
    private $from;

    /**
     * @var string
     *
     * @ORM\Column(name="headers_message", type="string", length=500, nullable=false, options={"collation": "utf8mb4_unicode_ci"})
     */
    private $headers;

    /**
     * @var string
     *
     * @ORM\Column(name="code_message", type="string", length=30, nullable=false, options={"collation": "utf8mb4_unicode_ci"})
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="cont_message", type="text", length=65535, nullable=false, options={"collation": "utf8mb4_unicode_ci"})
     */
    private $cont;

    /**
     * @var bool
     *
     * @ORM\Column(name="success_message", type="boolean", nullable=false)
     */
    private $success;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function setDate(string $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getTo(): ?string
    {
        return $this->to;
    }

    public function setTo(string $to): self
    {
        $this->to = $to;

        return $this;
    }

    public function getFrom(): ?string
    {
        return $this->from;
    }

    public function setFrom(string $from): self
    {
        $this->from = $from;

        return $this;
    }

    public function getHeaders(): ?string
    {
        return $this->headers;
    }

    public function setHeaders(string $headers): self
    {
        $this->headers = $headers;

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

    public function getCont(): ?string
    {
        return $this->cont;
    }

    public function setCont(string $cont): self
    {
        $this->cont = $cont;

        return $this;
    }

    public function getSuccess(): ?bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success): self
    {
        $this->success = $success;

        return $this;
    }
}
