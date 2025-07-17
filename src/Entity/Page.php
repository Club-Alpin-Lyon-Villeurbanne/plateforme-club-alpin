<?php

namespace App\Entity;

use App\Repository\PageRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Page.
 */
#[ORM\Table(name: 'caf_page')]
#[ORM\Entity(repositoryClass: PageRepository::class)]
class Page
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id_page', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var int
     */
    #[ORM\Column(name: 'parent_page', type: 'integer', nullable: false)]
    private $parent;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'admin_page', type: 'boolean', nullable: false, options: ['comment' => 'Protection et mise en page de page admin (!=public)'])]
    private $admin;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'superadmin_page', type: 'boolean', nullable: false, options: ['comment' => 'Page réservée au super-administrateur. "Contenu" dans le niveau administrateur dans la hiérarchie des filtres sur le site : admin_page doit donc aussi etre activé'])]
    private $superadmin = '0';

    /**
     * @var bool
     */
    #[ORM\Column(name: 'vis_page', type: 'boolean', nullable: false, options: ['default' => '1', 'comment' => 'On / Off'])]
    private $vis = true;

    /**
     * @var int
     */
    #[ORM\Column(name: 'ordre_page', type: 'integer', nullable: false)]
    private $ordre;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'menu_page', type: 'boolean', nullable: false, options: ['comment' => 'Apparait dans le menu principal ?'])]
    private $menu;

    /**
     * @var int
     */
    #[ORM\Column(name: 'ordre_menu_page', type: 'integer', nullable: false, options: ['comment' => 'Position dans le menu ppal'])]
    private $ordreMenu;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'menuadmin_page', type: 'boolean', nullable: false, options: ['comment' => 'Apparait dans le menu admin ?'])]
    private $menuadmin;

    /**
     * @var int
     */
    #[ORM\Column(name: 'ordre_menuadmin_page', type: 'integer', nullable: false, options: ['comment' => 'Position dans le menu admin'])]
    private $ordreMenuadmin;

    /**
     * @var string
     */
    #[ORM\Column(name: 'code_page', type: 'string', length: 50, nullable: false, options: ['comment' => 'ID lié au nom des fichiers et des variables'])]
    private $code;

    /**
     * @var string
     */
    #[ORM\Column(name: 'default_name_page', type: 'string', length: 100, nullable: false, options: ['comment' => 'Pour les pages admin notamment'])]
    private $defaultName;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'meta_title_page', type: 'boolean', nullable: false, options: ['comment' => 'Booléen : utiliser un titre sur mesure ou pas'])]
    private $metaTitle = '0';

    /**
     * @var bool
     */
    #[ORM\Column(name: 'meta_description_page', type: 'boolean', nullable: false, options: ['comment' => 'Booléen : utiliser une description sur mesure ou pas'])]
    private $metaDescription = '0';

    /**
     * @var string
     */
    #[ORM\Column(name: 'priority_page', type: 'decimal', precision: 1, scale: 1, nullable: false, options: ['comment' => 'Priorité de sitemap'])]
    private $priority;

    /**
     * @var string
     */
    #[ORM\Column(name: 'add_css_page', type: 'string', length: 200, nullable: false, options: ['comment' => 'Liste de fichiers css à ajouter, séparés par ;'])]
    private $addCss;

    /**
     * @var string
     */
    #[ORM\Column(name: 'add_js_page', type: 'string', length: 200, nullable: false, options: ['comment' => 'Liste de fichiers js à ajouter, séparés par ;'])]
    private $addJs;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'lock_page', type: 'boolean', nullable: false, options: ['comment' => 'Bloquer l-édition même au superadmin'])]
    private $lock;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'pagelibre_page', type: 'boolean', nullable: false, options: ['comment' => 'Pour le module de créatino de pages libres. Pour les pages standarts, comme des articles Wordpress'])]
    private $pagelibre = '0';

    /**
     * @var int
     */
    #[ORM\Column(name: 'created_page', type: 'bigint', nullable: false)]
    private $created;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParent(): ?int
    {
        return $this->parent;
    }

    public function setParent(int $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getAdmin(): ?bool
    {
        return $this->admin;
    }

    public function setAdmin(bool $admin): self
    {
        $this->admin = $admin;

        return $this;
    }

    public function getSuperadmin(): ?bool
    {
        return $this->superadmin;
    }

    public function setSuperadmin(bool $superadmin): self
    {
        $this->superadmin = $superadmin;

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

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): self
    {
        $this->ordre = $ordre;

        return $this;
    }

    public function getMenu(): ?bool
    {
        return $this->menu;
    }

    public function setMenu(bool $menu): self
    {
        $this->menu = $menu;

        return $this;
    }

    public function getOrdreMenu(): ?int
    {
        return $this->ordreMenu;
    }

    public function setOrdreMenu(int $ordreMenu): self
    {
        $this->ordreMenu = $ordreMenu;

        return $this;
    }

    public function getMenuadmin(): ?bool
    {
        return $this->menuadmin;
    }

    public function setMenuadmin(bool $menuadmin): self
    {
        $this->menuadmin = $menuadmin;

        return $this;
    }

    public function getOrdreMenuadmin(): ?int
    {
        return $this->ordreMenuadmin;
    }

    public function setOrdreMenuadmin(int $ordreMenuadmin): self
    {
        $this->ordreMenuadmin = $ordreMenuadmin;

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

    public function getDefaultName(): ?string
    {
        return $this->defaultName;
    }

    public function setDefaultName(string $defaultName): self
    {
        $this->defaultName = $defaultName;

        return $this;
    }

    public function getMetaTitle(): ?bool
    {
        return $this->metaTitle;
    }

    public function setMetaTitle(bool $metaTitle): self
    {
        $this->metaTitle = $metaTitle;

        return $this;
    }

    public function getMetaDescription(): ?bool
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(bool $metaDescription): self
    {
        $this->metaDescription = $metaDescription;

        return $this;
    }

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function getAddCss(): ?string
    {
        return $this->addCss;
    }

    public function setAddCss(string $addCss): self
    {
        $this->addCss = $addCss;

        return $this;
    }

    public function getAddJs(): ?string
    {
        return $this->addJs;
    }

    public function setAddJs(string $addJs): self
    {
        $this->addJs = $addJs;

        return $this;
    }

    public function getLock(): ?bool
    {
        return $this->lock;
    }

    public function setLock(bool $lock): self
    {
        $this->lock = $lock;

        return $this;
    }

    public function getPagelibre(): ?bool
    {
        return $this->pagelibre;
    }

    public function setPagelibre(bool $pagelibre): self
    {
        $this->pagelibre = $pagelibre;

        return $this;
    }

    public function getCreated(): ?string
    {
        return $this->created;
    }

    public function setCreated(string $created): self
    {
        $this->created = $created;

        return $this;
    }
}
