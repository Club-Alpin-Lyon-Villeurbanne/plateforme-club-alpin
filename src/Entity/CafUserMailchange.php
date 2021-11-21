<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CafUserMailchange.
 *
 * @ORM\Table(name="caf_user_mailchange")
 * @ORM\Entity
 */
class CafUserMailchange
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_user_mailchange", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idUserMailchange;

    /**
     * @var int
     *
     * @ORM\Column(name="user_user_mailchange", type="integer", nullable=false)
     */
    private $userUserMailchange;

    /**
     * @var string
     *
     * @ORM\Column(name="token_user_mailchange", type="string", length=32, nullable=false)
     */
    private $tokenUserMailchange;

    /**
     * @var string
     *
     * @ORM\Column(name="email_user_mailchange", type="string", length=200, nullable=false)
     */
    private $emailUserMailchange;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="time_user_mailchange", type="datetime", nullable=false, options={"default": "CURRENT_TIMESTAMP"})
     */
    private $timeUserMailchange = 'CURRENT_TIMESTAMP';

    public function getIdUserMailchange(): ?int
    {
        return $this->idUserMailchange;
    }

    public function getUserUserMailchange(): ?int
    {
        return $this->userUserMailchange;
    }

    public function setUserUserMailchange(int $userUserMailchange): self
    {
        $this->userUserMailchange = $userUserMailchange;

        return $this;
    }

    public function getTokenUserMailchange(): ?string
    {
        return $this->tokenUserMailchange;
    }

    public function setTokenUserMailchange(string $tokenUserMailchange): self
    {
        $this->tokenUserMailchange = $tokenUserMailchange;

        return $this;
    }

    public function getEmailUserMailchange(): ?string
    {
        return $this->emailUserMailchange;
    }

    public function setEmailUserMailchange(string $emailUserMailchange): self
    {
        $this->emailUserMailchange = $emailUserMailchange;

        return $this;
    }

    public function getTimeUserMailchange(): ?\DateTimeInterface
    {
        return $this->timeUserMailchange;
    }

    public function setTimeUserMailchange(\DateTimeInterface $timeUserMailchange): self
    {
        $this->timeUserMailchange = $timeUserMailchange;

        return $this;
    }
}
