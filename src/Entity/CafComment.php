<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CafComment.
 *
 * @ORM\Table(name="caf_comment")
 * @ORM\Entity
 */
class CafComment
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_comment", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idComment;

    /**
     * @var int
     *
     * @ORM\Column(name="status_comment", type="integer", nullable=false, options={"default": "1"})
     */
    private $statusComment = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="tsp_comment", type="bigint", nullable=false)
     */
    private $tspComment;

    /**
     * @var int
     *
     * @ORM\Column(name="user_comment", type="integer", nullable=false)
     */
    private $userComment;

    /**
     * @var string
     *
     * @ORM\Column(name="name_comment", type="string", length=50, nullable=false)
     */
    private $nameComment;

    /**
     * @var string
     *
     * @ORM\Column(name="email_comment", type="string", length=150, nullable=false)
     */
    private $emailComment;

    /**
     * @var string
     *
     * @ORM\Column(name="cont_comment", type="text", length=65535, nullable=false)
     */
    private $contComment;

    /**
     * @var string
     *
     * @ORM\Column(name="parent_type_comment", type="string", length=20, nullable=false)
     */
    private $parentTypeComment;

    /**
     * @var int
     *
     * @ORM\Column(name="parent_comment", type="integer", nullable=false)
     */
    private $parentComment;

    public function getIdComment(): ?int
    {
        return $this->idComment;
    }

    public function getStatusComment(): ?int
    {
        return $this->statusComment;
    }

    public function setStatusComment(int $statusComment): self
    {
        $this->statusComment = $statusComment;

        return $this;
    }

    public function getTspComment(): ?string
    {
        return $this->tspComment;
    }

    public function setTspComment(string $tspComment): self
    {
        $this->tspComment = $tspComment;

        return $this;
    }

    public function getUserComment(): ?int
    {
        return $this->userComment;
    }

    public function setUserComment(int $userComment): self
    {
        $this->userComment = $userComment;

        return $this;
    }

    public function getNameComment(): ?string
    {
        return $this->nameComment;
    }

    public function setNameComment(string $nameComment): self
    {
        $this->nameComment = $nameComment;

        return $this;
    }

    public function getEmailComment(): ?string
    {
        return $this->emailComment;
    }

    public function setEmailComment(string $emailComment): self
    {
        $this->emailComment = $emailComment;

        return $this;
    }

    public function getContComment(): ?string
    {
        return $this->contComment;
    }

    public function setContComment(string $contComment): self
    {
        $this->contComment = $contComment;

        return $this;
    }

    public function getParentTypeComment(): ?string
    {
        return $this->parentTypeComment;
    }

    public function setParentTypeComment(string $parentTypeComment): self
    {
        $this->parentTypeComment = $parentTypeComment;

        return $this;
    }

    public function getParentComment(): ?int
    {
        return $this->parentComment;
    }

    public function setParentComment(int $parentComment): self
    {
        $this->parentComment = $parentComment;

        return $this;
    }
}
