<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CafContentInline.
 *
 * @ORM\Table(name="caf_content_inline", indexes={@ORM\Index(name="contenu_content_inline", columns={"contenu_content_inline"})})
 * @ORM\Entity
 */
class CafContentInline
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_content_inline", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idContentInline;

    /**
     * @var int
     *
     * @ORM\Column(name="groupe_content_inline", type="integer", nullable=false, options={"comment": "Le parent de ce contenu, dans l'organisation pour l'administrateur"})
     */
    private $groupeContentInline;

    /**
     * @var string
     *
     * @ORM\Column(name="code_content_inline", type="string", length=100, nullable=false)
     */
    private $codeContentInline;

    /**
     * @var string
     *
     * @ORM\Column(name="lang_content_inline", type="string", length=2, nullable=false)
     */
    private $langContentInline;

    /**
     * @var string
     *
     * @ORM\Column(name="contenu_content_inline", type="text", length=65535, nullable=false)
     */
    private $contenuContentInline;

    /**
     * @var int
     *
     * @ORM\Column(name="date_content_inline", type="bigint", nullable=false)
     */
    private $dateContentInline;

    /**
     * @var string
     *
     * @ORM\Column(name="linkedtopage_content_inline", type="string", length=200, nullable=false, options={"comment": "URL relative de la page liée par défaut à cet élément, pour coupler à un moteur de recherche"})
     */
    private $linkedtopageContentInline;

    public function getIdContentInline(): ?int
    {
        return $this->idContentInline;
    }

    public function getGroupeContentInline(): ?int
    {
        return $this->groupeContentInline;
    }

    public function setGroupeContentInline(int $groupeContentInline): self
    {
        $this->groupeContentInline = $groupeContentInline;

        return $this;
    }

    public function getCodeContentInline(): ?string
    {
        return $this->codeContentInline;
    }

    public function setCodeContentInline(string $codeContentInline): self
    {
        $this->codeContentInline = $codeContentInline;

        return $this;
    }

    public function getLangContentInline(): ?string
    {
        return $this->langContentInline;
    }

    public function setLangContentInline(string $langContentInline): self
    {
        $this->langContentInline = $langContentInline;

        return $this;
    }

    public function getContenuContentInline(): ?string
    {
        return $this->contenuContentInline;
    }

    public function setContenuContentInline(string $contenuContentInline): self
    {
        $this->contenuContentInline = $contenuContentInline;

        return $this;
    }

    public function getDateContentInline(): ?string
    {
        return $this->dateContentInline;
    }

    public function setDateContentInline(string $dateContentInline): self
    {
        $this->dateContentInline = $dateContentInline;

        return $this;
    }

    public function getLinkedtopageContentInline(): ?string
    {
        return $this->linkedtopageContentInline;
    }

    public function setLinkedtopageContentInline(string $linkedtopageContentInline): self
    {
        $this->linkedtopageContentInline = $linkedtopageContentInline;

        return $this;
    }
}
