<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserMdpchange.
 *
 * @ORM\Table(name="caf_user_mdpchange")
 * @ORM\Entity
 */
class UserMdpchange
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_user_mdpchange", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="user_user_mdpchange", type="integer", nullable=false)
     */
    private $user;

    /**
     * @var string
     *
     * @ORM\Column(name="token_user_mdpchange", type="string", length=32, nullable=false)
     */
    private $token;

    /**
     * @var string
     *
     * @ORM\Column(name="pwd_user_mdpchange", type="string", length=32, nullable=false)
     */
    private $pwd;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="time_user_mdpchange", type="datetime", nullable=false, options={"default": "CURRENT_TIMESTAMP"})
     */
    private $time = 'CURRENT_TIMESTAMP';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?int
    {
        return $this->user;
    }

    public function setUser(int $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getPwd(): ?string
    {
        return $this->pwd;
    }

    public function setPwd(string $pwd): self
    {
        $this->pwd = $pwd;

        return $this;
    }

    public function getTime(): ?\DateTimeInterface
    {
        return $this->time;
    }

    public function setTime(\DateTimeInterface $time): self
    {
        $this->time = $time;

        return $this;
    }
}
