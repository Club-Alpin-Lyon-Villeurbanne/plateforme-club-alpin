<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CafContentHtml.
 *
 * @ORM\Table(name="caf_content_html", indexes={@ORM\Index(name="contenu_content_html", columns={"contenu_content_html"})})
 * @ORM\Entity
 */
class CafContentHtml
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_content_html", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idContentHtml;

    /**
     * @var string
     *
     * @ORM\Column(name="code_content_html", type="string", length=100, nullable=false, unique=true)
     */
    private $codeContentHtml;

    /**
     * @var string
     *
     * @ORM\Column(name="lang_content_html", type="string", length=2, nullable=false)
     */
    private $langContentHtml;

    /**
     * @var string
     *
     * @ORM\Column(name="contenu_content_html", type="text", length=65535, nullable=false)
     */
    private $contenuContentHtml;

    /**
     * @var int
     *
     * @ORM\Column(name="date_content_html", type="bigint", nullable=false)
     */
    private $dateContentHtml;

    /**
     * @var string
     *
     * @ORM\Column(name="linkedtopage_content_html", type="string", length=200, nullable=false, options={"comment": "URL relative de la page liée par défaut à cet élément, pour coupler à un moteur de recherche"})
     */
    private $linkedtopageContentHtml;

    /**
     * @var bool
     *
     * @ORM\Column(name="current_content_html", type="boolean", nullable=false, options={"comment": "Définit le dernier élément en date, pour simplifier les requêtes de recherche"})
     */
    private $currentContentHtml = '0';

    /**
     * @var bool
     *
     * @ORM\Column(name="vis_content_html", type="boolean", nullable=false, options={"default": "1"})
     */
    private $visContentHtml = true;

    public function getIdContentHtml(): ?int
    {
        return $this->idContentHtml;
    }

    public function getCodeContentHtml(): ?string
    {
        return $this->codeContentHtml;
    }

    public function setCodeContentHtml(string $codeContentHtml): self
    {
        $this->codeContentHtml = $codeContentHtml;

        return $this;
    }

    public function getLangContentHtml(): ?string
    {
        return $this->langContentHtml;
    }

    public function setLangContentHtml(string $langContentHtml): self
    {
        $this->langContentHtml = $langContentHtml;

        return $this;
    }

    public function getContenuContentHtml(): ?string
    {
        return $this->contenuContentHtml;
    }

    public function setContenuContentHtml(string $contenuContentHtml): self
    {
        $this->contenuContentHtml = $contenuContentHtml;

        return $this;
    }

    public function getDateContentHtml(): ?string
    {
        return $this->dateContentHtml;
    }

    public function setDateContentHtml(string $dateContentHtml): self
    {
        $this->dateContentHtml = $dateContentHtml;

        return $this;
    }

    public function getLinkedtopageContentHtml(): ?string
    {
        return $this->linkedtopageContentHtml;
    }

    public function setLinkedtopageContentHtml(string $linkedtopageContentHtml): self
    {
        $this->linkedtopageContentHtml = $linkedtopageContentHtml;

        return $this;
    }

    public function getCurrentContentHtml(): ?bool
    {
        return $this->currentContentHtml;
    }

    public function setCurrentContentHtml(bool $currentContentHtml): self
    {
        $this->currentContentHtml = $currentContentHtml;

        return $this;
    }

    public function getVisContentHtml(): ?bool
    {
        return $this->visContentHtml;
    }

    public function setVisContentHtml(bool $visContentHtml): self
    {
        $this->visContentHtml = $visContentHtml;

        return $this;
    }
}
