<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ContentInline.
 */
#[ORM\Table(name: 'caf_content_inline')]
#[ORM\Index(columns: ['code_content_inline'], name: 'code_content_inline')]
#[ORM\Index(columns: ['contenu_content_inline'], name: 'contenu_content_inline')]
#[ORM\Entity]
class ContentInline
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id_content_inline', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var int
     */
    #[ORM\Column(name: 'groupe_content_inline', type: 'integer', nullable: false, options: ['comment' => "Le parent de ce contenu, dans l'organisation pour l'administrateur"])]
    private $groupe;

    /**
     * @var string
     */
    #[ORM\Column(name: 'code_content_inline', type: 'string', length: 100, nullable: false)]
    private $code;

    /**
     * @var string
     */
    #[ORM\Column(name: 'lang_content_inline', type: 'string', length: 2, nullable: false)]
    private $lang;

    /**
     * @var string
     */
    #[ORM\Column(name: 'contenu_content_inline', type: 'text', length: 65535, nullable: false)]
    private $contenu;

    /**
     * @var int
     */
    #[ORM\Column(name: 'date_content_inline', type: 'bigint', nullable: false)]
    private $date;

    /**
     * @var string
     */
    #[ORM\Column(name: 'linkedtopage_content_inline', type: 'string', length: 200, nullable: false, options: ['comment' => 'URL relative de la page liée par défaut à cet élément, pour coupler à un moteur de recherche'])]
    private $linkedtopage;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGroupe(): ?int
    {
        return $this->groupe;
    }

    public function setGroupe(int $groupe): self
    {
        $this->groupe = $groupe;

        return $this;
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
}
