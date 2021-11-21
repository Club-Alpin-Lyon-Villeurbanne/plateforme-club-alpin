<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CafUserMdpchange.
 *
 * @ORM\Table(name="caf_user_mdpchange")
 * @ORM\Entity
 */
class CafUserMdpchange
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_user_mdpchange", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idUserMdpchange;

    /**
     * @var int
     *
     * @ORM\Column(name="user_user_mdpchange", type="integer", nullable=false)
     */
    private $userUserMdpchange;

    /**
     * @var string
     *
     * @ORM\Column(name="token_user_mdpchange", type="string", length=32, nullable=false)
     */
    private $tokenUserMdpchange;

    /**
     * @var string
     *
     * @ORM\Column(name="pwd_user_mdpchange", type="string", length=32, nullable=false)
     */
    private $pwdUserMdpchange;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="time_user_mdpchange", type="datetime", nullable=false, options={"default": "CURRENT_TIMESTAMP"})
     */
    private $timeUserMdpchange = 'CURRENT_TIMESTAMP';

    public function getIdUserMdpchange(): ?int
    {
        return $this->idUserMdpchange;
    }

    public function getUserUserMdpchange(): ?int
    {
        return $this->userUserMdpchange;
    }

    public function setUserUserMdpchange(int $userUserMdpchange): self
    {
        $this->userUserMdpchange = $userUserMdpchange;

        return $this;
    }

    public function getTokenUserMdpchange(): ?string
    {
        return $this->tokenUserMdpchange;
    }

    public function setTokenUserMdpchange(string $tokenUserMdpchange): self
    {
        $this->tokenUserMdpchange = $tokenUserMdpchange;

        return $this;
    }

    public function getPwdUserMdpchange(): ?string
    {
        return $this->pwdUserMdpchange;
    }

    public function setPwdUserMdpchange(string $pwdUserMdpchange): self
    {
        $this->pwdUserMdpchange = $pwdUserMdpchange;

        return $this;
    }

    public function getTimeUserMdpchange(): ?\DateTimeInterface
    {
        return $this->timeUserMdpchange;
    }

    public function setTimeUserMdpchange(\DateTimeInterface $timeUserMdpchange): self
    {
        $this->timeUserMdpchange = $timeUserMdpchange;

        return $this;
    }
}
