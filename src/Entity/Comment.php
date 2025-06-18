<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Comment.
 */
#[ORM\Table(name: 'caf_comment')]
#[ORM\Entity]
class Comment
{
    public const string ARTICLE_TYPE = 'article';

    /**
     * @var int
     */
    #[ORM\Column(name: 'id_comment', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var int
     */
    #[ORM\Column(name: 'status_comment', type: 'integer', nullable: false, options: ['default' => '1'])]
    private $status = 1;

    /**
     * @var int
     */
    #[ORM\Column(name: 'tsp_comment', type: 'bigint', nullable: false)]
    private $tsp;

    /**
     * @var User
     */
    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'user_comment', referencedColumnName: 'id_user', nullable: false)]
    private $user;

    /**
     * @var string
     */
    #[ORM\Column(name: 'name_comment', type: 'string', length: 50, nullable: false)]
    private $name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'email_comment', type: 'string', length: 150, nullable: false)]
    private $email;

    /**
     * @var string
     */
    #[ORM\Column(name: 'cont_comment', type: 'text', length: 65535, nullable: false)]
    private $cont;

    /**
     * @var string
     */
    #[ORM\Column(name: 'parent_type_comment', type: 'string', length: 20, nullable: false)]
    private $parentType;

    /**
     * @var int
     */
    #[ORM\Column(name: 'parent_comment', type: 'integer', nullable: false)]
    private $parent;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getTsp(): ?string
    {
        return $this->tsp;
    }

    public function setTsp(string $tsp): self
    {
        $this->tsp = $tsp;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getCont(): ?string
    {
        return $this->cont;
    }

    public function setCont(string $cont): self
    {
        $this->cont = $cont;

        return $this;
    }

    public function getParentType(): ?string
    {
        return $this->parentType;
    }

    public function setParentType(string $parentType): self
    {
        $this->parentType = $parentType;

        return $this;
    }

    public function getParent(): ?int
    {
        return $this->parent;
    }

    public function setParent(int $parent): self
    {
        $this->parent = $parent;

        return $this;
    }
}
