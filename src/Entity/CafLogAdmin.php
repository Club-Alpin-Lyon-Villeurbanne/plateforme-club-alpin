<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CafLogAdmin.
 *
 * @ORM\Table(name="caf_log_admin")
 * @ORM\Entity
 */
class CafLogAdmin
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_log_admin", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idLogAdmin;

    /**
     * @var string
     *
     * @ORM\Column(name="code_log_admin", type="string", length=100, nullable=false)
     */
    private $codeLogAdmin;

    /**
     * @var string
     *
     * @ORM\Column(name="desc_log_admin", type="string", length=200, nullable=false)
     */
    private $descLogAdmin;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ip_log_admin", type="string", length=15, nullable=true)
     */
    private $ipLogAdmin;

    /**
     * @var int
     *
     * @ORM\Column(name="date_log_admin", type="bigint", nullable=false)
     */
    private $dateLogAdmin;

    public function getIdLogAdmin(): ?int
    {
        return $this->idLogAdmin;
    }

    public function getCodeLogAdmin(): ?string
    {
        return $this->codeLogAdmin;
    }

    public function setCodeLogAdmin(string $codeLogAdmin): self
    {
        $this->codeLogAdmin = $codeLogAdmin;

        return $this;
    }

    public function getDescLogAdmin(): ?string
    {
        return $this->descLogAdmin;
    }

    public function setDescLogAdmin(string $descLogAdmin): self
    {
        $this->descLogAdmin = $descLogAdmin;

        return $this;
    }

    public function getIpLogAdmin(): ?string
    {
        return $this->ipLogAdmin;
    }

    public function setIpLogAdmin(?string $ipLogAdmin): self
    {
        $this->ipLogAdmin = $ipLogAdmin;

        return $this;
    }

    public function getDateLogAdmin(): ?string
    {
        return $this->dateLogAdmin;
    }

    public function setDateLogAdmin(string $dateLogAdmin): self
    {
        $this->dateLogAdmin = $dateLogAdmin;

        return $this;
    }
}
