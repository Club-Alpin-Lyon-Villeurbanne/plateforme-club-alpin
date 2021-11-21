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
     * @var int
     *
     * @ORM\Column(name="user_user_attr", type="integer", nullable=false, options={"comment": "ID user possédant le type "})
     */
    private $userUserAttr;

    /**
     * @var int
     *
     * @ORM\Column(name="usertype_user_attr", type="integer", nullable=false, options={"comment": "ID du type (admin, modero etc...)"})
     */
    private $usertypeUserAttr;

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

    public function getUserUserAttr(): ?int
    {
        return $this->userUserAttr;
    }

    public function setUserUserAttr(int $userUserAttr): self
    {
        $this->userUserAttr = $userUserAttr;

        return $this;
    }

    public function getUsertypeUserAttr(): ?int
    {
        return $this->usertypeUserAttr;
    }

    public function setUsertypeUserAttr(int $usertypeUserAttr): self
    {
        $this->usertypeUserAttr = $usertypeUserAttr;

        return $this;
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
