<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Token.
 *
 *
 */
#[ORM\Table(name: 'caf_token')]
#[ORM\Entity]
class Token
{
    /**
     * @var string
     *
     *
     *
     */
    #[ORM\Column(name: 'id_token', type: 'string', length: 32, nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var int
     */
    #[ORM\Column(name: 'time_token', type: 'bigint', nullable: false)]
    private $time;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getTime(): ?string
    {
        return $this->time;
    }

    public function setTime(string $time): self
    {
        $this->time = $time;

        return $this;
    }
}
