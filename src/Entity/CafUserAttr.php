<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CafUserAttr.
 *
 * @ORM\Table(name="caf_user_attr")
 * @ORM\Entity
 */
class CafUserAttr
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_user_attr", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idUserAttr;

    /**
     * @ORM\ManyToOne(targetEntity="CafUser", inversedBy="attrs", fetch="EAGER")
     * @ORM\JoinColumn(name="user_user_attr", referencedColumnName="id_user", nullable=false, onDelete="CASCADE")
     */
    private $user;

    /**
     * @ORM\OneToOne(targetEntity="CafUsertype")
     * @ORM\JoinColumn(name="usertype_user_attr", referencedColumnName="id_usertype")
     */
    private $userType;

    /**
     * @var string
     *
     * @ORM\Column(name="params_user_attr", type="string", length=200, nullable=false)
     */
    private $paramsUserAttr;

    /**
     * @var string
     *
     * @ORM\Column(name="details_user_attr", type="string", length=100, nullable=false, options={"comment": "date - de qui... ?"})
     */
    private $detailsUserAttr;

    public function getIdUserAttr(): ?int
    {
        return $this->idUserAttr;
    }

    public function getUserId(): ?int
    {
        return $this->user->getIdUser();
    }

    public function getUserType(): ?CafUsertype
    {
        return $this->userType;
    }

    public function getParamsUserAttr(): ?string
    {
        return $this->paramsUserAttr;
    }

    public function setParamsUserAttr(string $paramsUserAttr): self
    {
        $this->paramsUserAttr = $paramsUserAttr;

        return $this;
    }

    public function getDetailsUserAttr(): ?string
    {
        return $this->detailsUserAttr;
    }

    public function setDetailsUserAttr(string $detailsUserAttr): self
    {
        $this->detailsUserAttr = $detailsUserAttr;

        return $this;
    }
}
