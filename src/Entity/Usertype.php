<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'caf_usertype')]
#[ORM\Entity]
class Usertype
{
    #[ORM\Column(name: 'id_usertype', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(name: 'hierarchie_usertype', type: 'integer', nullable: false, options: ['comment' => "Ordre d'apparition des types"])]
    private ?int $hierarchie = null;

    #[ORM\Column(name: 'code_usertype', type: 'string', length: 30, nullable: false)]
    private ?string $code = null;

    #[ORM\Column(name: 'title_usertype', type: 'string', length: 30, nullable: false)]
    private ?string $title = null;

    #[ORM\Column(name: 'limited_to_comm_usertype', type: 'boolean', nullable: false, options: ['comment' => 'bool : ce type est (ou non) limité à une commission donnée'])]
    private ?bool $limitedToComm = null;

    #[ORM\OneToMany(targetEntity: UsertypeAttr::class, mappedBy: 'type')]
    private Collection $usertypeAttrs;

    public function __construct()
    {
        $this->usertypeAttrs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHierarchie(): ?int
    {
        return $this->hierarchie;
    }

    public function setHierarchie(int $hierarchie): self
    {
        $this->hierarchie = $hierarchie;
        return $this;
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

    public function getLimitedToComm(): ?bool
    {
        return $this->limitedToComm;
    }

    public function setLimitedToComm(bool $limitedToComm): self
    {
        $this->limitedToComm = $limitedToComm;
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
            $usertypeAttr->setType($this);
        }
        return $this;
    }

    public function removeUsertypeAttr(UsertypeAttr $usertypeAttr): self
    {
        if ($this->usertypeAttrs->removeElement($usertypeAttr)) {
            // set the owning side to null (unless already changed)
            if ($usertypeAttr->getType() === $this) {
                $usertypeAttr->setType(null);
            }
        }
        return $this;
    }
}