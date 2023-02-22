<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserNiveau.
 *
 * @ORM\Table(name="caf_user_niveau")
 *
 * @ORM\Entity
 */
class UserNiveau
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned": true})
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User")
     *
     * @ORM\JoinColumn(name="id_user", referencedColumnName="id_user", nullable=false)
     */
    private $idUser;

    /**
     * @var int
     *
     * @ORM\Column(name="id_commission", type="integer", nullable=false, options={"unsigned": true})
     */
    private $idCommission;

    /**
     * @var int|null
     *
     * @ORM\Column(name="niveau_technique", type="smallint", nullable=true, options={"unsigned": true})
     */
    private $niveauTechnique;

    /**
     * @var int|null
     *
     * @ORM\Column(name="niveau_physique", type="smallint", nullable=true, options={"unsigned": true})
     */
    private $niveauPhysique;

    /**
     * @var string|null
     *
     * @ORM\Column(name="commentaire", type="text", length=65535, nullable=true)
     */
    private $commentaire;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdUser(): ?User
    {
        return $this->idUser;
    }

    public function setIdUser(User $idUser): self
    {
        $this->idUser = $idUser;

        return $this;
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

    public function getNiveauTechnique(): ?int
    {
        return $this->niveauTechnique;
    }

    public function setNiveauTechnique(?int $niveauTechnique): self
    {
        $this->niveauTechnique = $niveauTechnique;

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

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): self
    {
        $this->commentaire = $commentaire;

        return $this;
    }
}
