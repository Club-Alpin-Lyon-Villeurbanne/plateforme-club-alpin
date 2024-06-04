<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Usertype.
 */
#[ORM\Table(name: 'caf_usertype')]
#[ORM\Entity]
class Usertype
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id_usertype', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'hierarchie_usertype', type: 'integer', nullable: false, options: ['comment' => "Ordre d'apparition des types"])]
    private $hierarchie;

    /**
     * @var string
     */
    #[ORM\Column(name: 'code_usertype', type: 'string', length: 30, nullable: false)]
    private $code;

    /**
     * @var string
     */
    #[ORM\Column(name: 'title_usertype', type: 'string', length: 30, nullable: false)]
    private $title;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'limited_to_comm_usertype', type: 'boolean', nullable: false, options: ['comment' => 'bool : ce type est (ou non) limité à une commission donnée'])]
    private $limitedToComm;

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
}
