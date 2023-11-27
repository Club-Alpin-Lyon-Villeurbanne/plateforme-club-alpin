<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Galerie.
 *
 *
 */
#[ORM\Table(name: 'caf_galerie')]
#[ORM\Entity]
class Galerie
{
    /**
     * @var int
     *
     *
     *
     */
    #[ORM\Column(name: 'id_galerie', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var int
     */
    #[ORM\Column(name: 'ordre_galerie', type: 'integer', nullable: false)]
    private $ordre;

    /**
     * @var string
     */
    #[ORM\Column(name: 'titre_galerie', type: 'string', length: 100, nullable: false)]
    private $titre;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'vis_galerie', type: 'boolean', nullable: false, options: ['default' => '1'])]
    private $vis = true;

    /**
     * @var int
     */
    #[ORM\Column(name: 'evt_galerie', type: 'integer', nullable: false, options: ['comment' => 'Sortie liée (facultatif)'])]
    private $evt;

    /**
     * @var int
     */
    #[ORM\Column(name: 'article_galerie', type: 'integer', nullable: false, options: ['comment' => 'Article lié (facultatif)'])]
    private $article;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): self
    {
        $this->ordre = $ordre;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;

        return $this;
    }

    public function getVis(): ?bool
    {
        return $this->vis;
    }

    public function setVis(bool $vis): self
    {
        $this->vis = $vis;

        return $this;
    }

    public function getEvt(): ?int
    {
        return $this->evt;
    }

    public function setEvt(int $evt): self
    {
        $this->evt = $evt;

        return $this;
    }

    public function getArticle(): ?int
    {
        return $this->article;
    }

    public function setArticle(int $article): self
    {
        $this->article = $article;

        return $this;
    }
}
