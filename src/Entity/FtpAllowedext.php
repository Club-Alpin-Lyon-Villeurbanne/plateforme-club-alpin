<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FtpAllowedext.
 *
 *
 */
#[ORM\Table(name: 'caf_ftp_allowedext')]
#[ORM\Entity]
class FtpAllowedext
{
    /**
     * @var int
     *
     *
     *
     */
    #[ORM\Column(name: 'id_ftp_allowedext', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var string
     */
    #[ORM\Column(name: 'ext_ftp_allowedext', type: 'string', length: 6, nullable: false)]
    private $ext;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExt(): ?string
    {
        return $this->ext;
    }

    public function setExt(string $ext): self
    {
        $this->ext = $ext;

        return $this;
    }
}
