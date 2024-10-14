<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'caf_usertype_attr')]
#[ORM\Entity]
class UsertypeAttr
{
    #[ORM\Column(name: 'id_usertype_attr', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: UserType::class, inversedBy: 'usertypeAttrs')]
    #[ORM\JoinColumn(name: 'type_usertype_attr', referencedColumnName: 'id_usertype', nullable: false)]
    private ?UserType $type = null;

    #[ORM\ManyToOne(targetEntity: UserRight::class, inversedBy: 'usertypeAttrs')]
    #[ORM\JoinColumn(name: 'right_usertype_attr', referencedColumnName: 'id_userright', nullable: false)]
    private ?UserRight $right = null;

    #[ORM\Column(name: 'details_usertype_attr', type: 'string', length: 100, nullable: false)]
    private ?string $details = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?UserType
    {
        return $this->type;
    }

    public function setType(?UserType $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getRight(): ?UserRight
    {
        return $this->right;
    }

    public function setRight(?UserRight $right): self
    {
        $this->right = $right;
        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(string $details): self
    {
        $this->details = $details;
        return $this;
    }
}