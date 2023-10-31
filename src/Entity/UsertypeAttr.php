<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UsertypeAttr.
 *
 *
 */
#[ORM\Table(name: 'caf_usertype_attr')]
#[ORM\Entity]
class UsertypeAttr
{
    /**
     * @var int
     *
     *
     *
     */
    #[ORM\Column(name: 'id_usertype_attr', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var int
     */
    #[ORM\Column(name: 'type_usertype_attr', type: 'integer', nullable: false, options: ['comment' => "ID du type d'user (admin, modéro etc...)"])]
    private $type;

    /**
     * @var int
     */
    #[ORM\Column(name: 'right_usertype_attr', type: 'integer', nullable: false, options: ['comment' => 'ID du droit dans la table userright'])]
    private $right;

    /**
     * @var string
     */
    #[ORM\Column(name: 'details_usertype_attr', type: 'string', length: 100, nullable: false)]
    private $details;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getRight(): ?int
    {
        return $this->right;
    }

    public function setRight(int $right): self
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
