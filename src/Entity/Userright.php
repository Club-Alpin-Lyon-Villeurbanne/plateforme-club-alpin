<?php

namespace App\Entity;

use App\Repository\UserRightRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRightRepository::class)]
#[ORM\Table(name: 'caf_userright')]
class UserRight
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_userright', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'code_userright', type: 'string', length: 40)]
    private ?string $code = null;

    #[ORM\Column(name: 'title_userright', type: 'string', length: 100)]
    private ?string $title = null;

    #[ORM\Column(name: 'ordre_userright', type: 'integer')]
    private ?int $order = null;

    #[ORM\Column(name: 'parent_userright', type: 'string', length: 40)]
    private ?string $parent = null;

    #[ORM\OneToMany(targetEntity: UsertypeAttr::class, mappedBy: 'right')]
    private Collection $usertypeAttrs;

    public function __construct()
    {
        $this->usertypeAttrs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getOrder(): ?int
    {
        return $this->order;
    }

    public function setOrder(int $order): self
    {
        $this->order = $order;
        return $this;
    }

    public function getParent(): ?string
    {
        return $this->parent;
    }

    public function setParent(string $parent): self
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * @return Collection<int, UsertypeAttr>
     */
    public function getUsertypeAttrs(): Collection
    {
        return $this->usertypeAttrs;
    }

    public function addUsertypeAttr(UsertypeAttr $usertypeAttr): self
    {
        if (!$this->usertypeAttrs->contains($usertypeAttr)) {
            $this->usertypeAttrs->add($usertypeAttr);
            $usertypeAttr->setRight($this);
        }
        return $this;
    }

    public function removeUsertypeAttr(UsertypeAttr $usertypeAttr): self
    {
        if ($this->usertypeAttrs->removeElement($usertypeAttr)) {
            // set the owning side to null (unless already changed)
            if ($usertypeAttr->getRight() === $this) {
                $usertypeAttr->setRight(null);
            }
        }
        return $this;
    }
}