<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CafGalerie.
 *
 * @ORM\Table(name="caf_galerie")
 * @ORM\Entity
 */
class CafGalerie
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_galerie", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idGalerie;

    /**
     * @var int
     *
     * @ORM\Column(name="ordre_galerie", type="integer", nullable=false)
     */
    private $ordreGalerie;

    /**
     * @var string
     *
     * @ORM\Column(name="titre_galerie", type="string", length=100, nullable=false)
     */
    private $titreGalerie;

    /**
     * @var bool
     *
     * @ORM\Column(name="vis_galerie", type="boolean", nullable=false, options={"default": "1"})
     */
    private $visGalerie = true;

    /**
     * @var int
     *
     * @ORM\Column(name="evt_galerie", type="integer", nullable=false, options={"comment": "Sortie liée (facultatif)"})
     */
    private $evtGalerie;

    /**
     * @var int
     *
     * @ORM\Column(name="article_galerie", type="integer", nullable=false, options={"comment": "Article lié (facultatif)"})
     */
    private $articleGalerie;

    public function getIdGalerie(): ?int
    {
        return $this->idGalerie;
    }

    public function getOrdreGalerie(): ?int
    {
        return $this->ordreGalerie;
    }

    public function setOrdreGalerie(int $ordreGalerie): self
    {
        $this->ordreGalerie = $ordreGalerie;

        return $this;
    }

    public function getTitreGalerie(): ?string
    {
        return $this->titreGalerie;
    }

    public function setTitreGalerie(string $titreGalerie): self
    {
        $this->titreGalerie = $titreGalerie;

        return $this;
    }

    public function getVisGalerie(): ?bool
    {
        return $this->visGalerie;
    }

    public function setVisGalerie(bool $visGalerie): self
    {
        $this->visGalerie = $visGalerie;

        return $this;
    }

    public function getEvtGalerie(): ?int
    {
        return $this->evtGalerie;
    }

    public function setEvtGalerie(int $evtGalerie): self
    {
        $this->evtGalerie = $evtGalerie;

        return $this;
    }

    public function getArticleGalerie(): ?int
    {
        return $this->articleGalerie;
    }

    public function setArticleGalerie(int $articleGalerie): self
    {
        $this->articleGalerie = $articleGalerie;

        return $this;
    }
}
