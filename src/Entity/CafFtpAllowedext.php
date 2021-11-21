<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CafFtpAllowedext.
 *
 * @ORM\Table(name="caf_ftp_allowedext")
 * @ORM\Entity
 */
class CafFtpAllowedext
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_ftp_allowedext", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idFtpAllowedext;

    /**
     * @var string
     *
     * @ORM\Column(name="ext_ftp_allowedext", type="string", length=6, nullable=false)
     */
    private $extFtpAllowedext;

    public function getIdFtpAllowedext(): ?int
    {
        return $this->idFtpAllowedext;
    }

    public function getExtFtpAllowedext(): ?string
    {
        return $this->extFtpAllowedext;
    }

    public function setExtFtpAllowedext(string $extFtpAllowedext): self
    {
        $this->extFtpAllowedext = $extFtpAllowedext;

        return $this;
    }
}
