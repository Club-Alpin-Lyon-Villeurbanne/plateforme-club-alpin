<?php

namespace App\Entity;

use App\Repository\ApiUserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Api User.
 *
 * @ORM\Table(name="caf_api_user")
 *
 * @ORM\Entity(repositoryClass=ApiUserRepository::class)
 */
class ApiUser implements UserInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_user", type="integer", nullable=false)
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="application", type="string", length=200, nullable=true, unique=true)
     */
    private $application;

    /**
     * @var string
     *
     * @ORM\Column(name="key", type="string", length=1024, nullable=true)
     */
    private $key;

    public function getId(): ?int
    {
        return null !== $this->id ? (int) $this->id : null;
    }

    public function getApplication(): ?string
    {
        return $this->application;
    }

    public function setApplication(string $application): self
    {
        $this->application = $application;

        return $this;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function setKey(string $key): self
    {
        $this->key = $key;

        return $this;
    }

    public function getPassword()
    {}
    public function getSalt()
    {}
    public function eraseCredentials()
    {}

    public function getRoles(): array
    {
        return [];
    }

    public function getUsername()
    {
        return $this->application;
    }

    public function getUserIdentifier(): string
    {
        return $this->id;
    }

}