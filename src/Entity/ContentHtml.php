<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ContentHtml.
 */
#[ORM\Table(name: 'caf_content_html')]
#[ORM\Index(columns: ['code_content_html'], name: 'code_content_html')]
#[ORM\Index(columns: ['contenu_content_html'], name: 'contenu_content_html')]
#[ORM\Entity]
class ContentHtml
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id_content_html', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var string
     */
    #[ORM\Column(name: 'code_content_html', type: 'string', length: 100, nullable: false)]
    private $code;

    /**
     * @var string
     */
    #[ORM\Column(name: 'lang_content_html', type: 'string', length: 2, nullable: false)]
    private $lang;

    /**
     * @var string
     */
    #[ORM\Column(name: 'contenu_content_html', type: 'text', length: 65535, nullable: false)]
    private $contenu;

    /**
     * @var int
     */
    #[ORM\Column(name: 'date_content_html', type: 'bigint', nullable: false)]
    private $date;

    /**
     * @var string
     */
    #[ORM\Column(name: 'linkedtopage_content_html', type: 'string', length: 200, nullable: false, options: ['comment' => 'URL relative de la page liée par défaut à cet élément, pour coupler à un moteur de recherche'])]
    private $linkedtopage;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'current_content_html', type: 'boolean', nullable: false, options: ['comment' => 'Définit le dernier élément en date, pour simplifier les requêtes de recherche'])]
    private $current = '0';

    /**
     * @var bool
     */
    #[ORM\Column(name: 'vis_content_html', type: 'boolean', nullable: false, options: ['default' => '1'])]
    private $vis = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getLang(): ?string
    {
        return $this->lang;
    }

    public function setLang(string $lang): self
    {
        $this->lang = $lang;

        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): self
    {
        $this->contenu = $contenu;

        return $this;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function setDate(string $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getLinkedtopage(): ?string
    {
        return $this->linkedtopage;
    }

    public function setLinkedtopage(string $linkedtopage): self
    {
        $this->linkedtopage = $linkedtopage;

        return $this;
    }

    public function getCurrent(): ?bool
    {
        return $this->current;
    }

    public function setCurrent(bool $current): self
    {
        $this->current = $current;

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
}
