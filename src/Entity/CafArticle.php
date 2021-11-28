<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CafArticle.
 *
 * @ORM\Table(name="caf_article", indexes={@ORM\Index(name="id_article", columns={"id_article"})})
 * @ORM\Entity
 */
class CafArticle
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_article", type="integer", nullable=false, options={"unsigned": true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idArticle;

    /**
     * @var int
     *
     * @ORM\Column(name="status_article", type="integer", nullable=false, options={"comment": "0=pas vu, 1=valide, 2=refusé"})
     */
    private $statusArticle = '0';

    /**
     * @var int
     *
     * @ORM\Column(name="status_who_article", type="integer", nullable=true, options={"comment": "ID du membre qui change le statut"})
     */
    private $statusWhoArticle;

    /**
     * @var int
     *
     * @ORM\Column(name="topubly_article", type="integer", nullable=false, options={"comment": "Demander la publication ? Ou laisser en standby"})
     */
    private $topublyArticle;

    /**
     * @var int
     *
     * @ORM\Column(name="tsp_crea_article", type="integer", nullable=false, options={"comment": "Timestamp de création de l'article"})
     */
    private $tspCreaArticle;

    /**
     * @var int
     *
     * @ORM\Column(name="tsp_validate_article", type="integer", nullable=true)
     */
    private $tspValidateArticle;

    /**
     * @var int
     *
     * @ORM\Column(name="tsp_article", type="integer", nullable=false, options={"comment": "Timestamp affiché de l'article"})
     */
    private $tspArticle;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="tsp_lastedit", type="datetime", nullable=false, options={"default": "CURRENT_TIMESTAMP", "comment": "Date de dernière modif"})
     */
    private $tspLastedit = 'CURRENT_TIMESTAMP';

    /**
     * @var int
     *
     * @ORM\Column(name="user_article", type="integer", nullable=false, options={"comment": "ID du créateur"})
     */
    private $userArticle;

    /**
     * @var string
     *
     * @ORM\Column(name="titre_article", type="string", length=200, nullable=false)
     */
    private $titreArticle;

    /**
     * @var string
     *
     * @ORM\Column(name="code_article", type="string", length=50, nullable=false, options={"comment": "Pour affichage dans les URL"})
     */
    private $codeArticle;

    /**
     * @ORM\ManyToOne(targetEntity="CafCommission")
     * @ORM\JoinColumn(name="commission_article", referencedColumnName="id_commission", nullable=true)
     */
    private $commission;

    /**
     * @var int
     *
     * @ORM\Column(name="evt_article", type="integer", nullable=false, options={"comment": "ID sortie liée"})
     */
    private $evtArticle;

    /**
     * @var bool
     *
     * @ORM\Column(name="une_article", type="boolean", nullable=false, options={"comment": "A la une ?"})
     */
    private $uneArticle = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="cont_article", type="text", length=65535, nullable=false)
     */
    private $contArticle;

    /**
     * @var int
     *
     * @ORM\Column(name="nb_vues_article", type="integer", nullable=false, options={"default": 0})
     */
    private $nbVuesArticle = '0';

    public function getIdArticle(): ?int
    {
        return $this->idArticle;
    }

    public function getStatusArticle(): ?int
    {
        return $this->statusArticle;
    }

    public function setStatusArticle(int $statusArticle): self
    {
        $this->statusArticle = $statusArticle;

        return $this;
    }

    public function getStatusWhoArticle(): ?int
    {
        return $this->statusWhoArticle;
    }

    public function setStatusWhoArticle(int $statusWhoArticle): self
    {
        $this->statusWhoArticle = $statusWhoArticle;

        return $this;
    }

    public function getTopublyArticle(): ?int
    {
        return $this->topublyArticle;
    }

    public function setTopublyArticle(int $topublyArticle): self
    {
        $this->topublyArticle = $topublyArticle;

        return $this;
    }

    public function getTspCreaArticle(): ?int
    {
        return $this->tspCreaArticle;
    }

    public function setTspCreaArticle(int $tspCreaArticle): self
    {
        $this->tspCreaArticle = $tspCreaArticle;

        return $this;
    }

    public function getTspValidateArticle(): ?int
    {
        return $this->tspValidateArticle;
    }

    public function setTspValidateArticle(int $tspValidateArticle): self
    {
        $this->tspValidateArticle = $tspValidateArticle;

        return $this;
    }

    public function getTspArticle(): ?int
    {
        return $this->tspArticle;
    }

    public function setTspArticle(int $tspArticle): self
    {
        $this->tspArticle = $tspArticle;

        return $this;
    }

    public function getTspLastedit(): ?\DateTimeInterface
    {
        return $this->tspLastedit;
    }

    public function setTspLastedit(\DateTimeInterface $tspLastedit): self
    {
        $this->tspLastedit = $tspLastedit;

        return $this;
    }

    public function getUserArticle(): ?int
    {
        return $this->userArticle;
    }

    public function setUserArticle(int $userArticle): self
    {
        $this->userArticle = $userArticle;

        return $this;
    }

    public function getTitreArticle(): ?string
    {
        return $this->titreArticle;
    }

    public function setTitreArticle(string $titreArticle): self
    {
        $this->titreArticle = $titreArticle;

        return $this;
    }

    public function getCodeArticle(): ?string
    {
        return $this->codeArticle;
    }

    public function setCodeArticle(string $codeArticle): self
    {
        $this->codeArticle = $codeArticle;

        return $this;
    }

    public function getCommission(): ?CafCommission
    {
        return $this->commission;
    }

    public function getEvtArticle(): ?int
    {
        return $this->evtArticle;
    }

    public function setEvtArticle(int $evtArticle): self
    {
        $this->evtArticle = $evtArticle;

        return $this;
    }

    public function getUneArticle(): ?bool
    {
        return $this->uneArticle;
    }

    public function setUneArticle(bool $uneArticle): self
    {
        $this->uneArticle = $uneArticle;

        return $this;
    }

    public function getContArticle(): ?string
    {
        return $this->contArticle;
    }

    public function setContArticle(string $contArticle): self
    {
        $this->contArticle = $contArticle;

        return $this;
    }

    public function getNbVuesArticle(): ?int
    {
        return $this->nbVuesArticle;
    }

    public function setNbVuesArticle(int $nbVuesArticle): self
    {
        $this->nbVuesArticle = $nbVuesArticle;

        return $this;
    }
}
