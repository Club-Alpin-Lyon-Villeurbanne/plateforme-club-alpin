<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CafToken.
 *
 * @ORM\Table(name="caf_token")
 * @ORM\Entity
 */
class CafToken
{
    /**
     * @var string
     *
     * @ORM\Column(name="id_token", type="string", length=32, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idToken;

    /**
     * @var int
     *
     * @ORM\Column(name="time_token", type="bigint", nullable=false)
     */
    private $timeToken;

    public function getIdToken(): ?string
    {
        return $this->idToken;
    }

    public function getTimeToken(): ?string
    {
        return $this->timeToken;
    }

    public function setTimeToken(string $timeToken): self
    {
        $this->timeToken = $timeToken;

        return $this;
    }
}
