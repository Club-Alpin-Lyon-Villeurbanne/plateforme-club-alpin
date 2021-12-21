<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LogAdmin.
 *
 * @ORM\Table(name="caf_log_admin")
 * @ORM\Entity
 */
class LogAdmin
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_log_admin", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="code_log_admin", type="string", length=100, nullable=false)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="desc_log_admin", type="string", length=200, nullable=false)
     */
    private $desc;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ip_log_admin", type="string", length=15, nullable=true)
     */
    private $ip;

    /**
     * @var int
     *
     * @ORM\Column(name="date_log_admin", type="bigint", nullable=false)
     */
    private $date;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDesc(): ?string
    {
        return $this->desc;
    }

    public function setDesc(string $desc): self
    {
        $this->desc = $desc;

        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): self
    {
        $this->ip = $ip;

        return $this;
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
}
