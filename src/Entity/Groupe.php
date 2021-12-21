<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Groupe.
 *
 * @ORM\Table(name="caf_groupe")
 * @ORM\Entity
 */
class Groupe
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned": true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="id_commission", type="integer", nullable=false, options={"unsigned": true})
     */
    private $idCommission;

    /**
     * @var string
     *
     * @ORM\Column(name="nom", type="string", length=100, nullable=false)
     */
    private $nom;

    /**
     * @var string|null
     *
     * @ORM\Column(name="description", type="text", length=65535, nullable=true)
     */
    private $description;

    /**
     * @var int|null
     *
     * @ORM\Column(name="niveau_physique", type="integer", nullable=true, options={"unsigned": true})
     */
    private $niveauPhysique;

    /**
     * @var int|null
     *
     * @ORM\Column(name="niveau_technique", type="integer", nullable=true, options={"unsigned": true})
     */
    private $niveauTechnique;

    /**
     * @var bool
     *
     * @ORM\Column(name="actif", type="boolean", nullable=false, options={"default": "1"})
     */
    private $actif = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdCommission(): ?int
    {
        return $this->idCommission;
    }

    public function setIdCommission(int $idCommission): self
    {
        $this->idCommission = $idCommission;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getNiveauPhysique(): ?int
    {
        return $this->niveauPhysique;
    }

    public function setNiveauPhysique(?int $niveauPhysique): self
    {
        $this->niveauPhysique = $niveauPhysique;

        return $this;
    }

    public function getNiveauTechnique(): ?int
    {
        return $this->niveauTechnique;
    }

    public function setNiveauTechnique(?int $niveauTechnique): self
    {
        $this->niveauTechnique = $niveauTechnique;

        return $this;
    }

    public function getActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): self
    {
        $this->actif = $actif;

        return $this;
    }
}
