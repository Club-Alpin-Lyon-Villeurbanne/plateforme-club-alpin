<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CafPage.
 *
 * @ORM\Table(name="caf_page")
 * @ORM\Entity
 */
class CafPage
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_page", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idPage;

    /**
     * @var int
     *
     * @ORM\Column(name="parent_page", type="integer", nullable=false)
     */
    private $parentPage;

    /**
     * @var bool
     *
     * @ORM\Column(name="admin_page", type="boolean", nullable=false, options={"comment": "Protection et mise en page de page admin (!=public)"})
     */
    private $adminPage;

    /**
     * @var bool
     *
     * @ORM\Column(name="superadmin_page", type="boolean", nullable=false, options={"comment": "Page réservée au super-administrateur. ""Contenu"" dans le niveau administrateur dans la hiérarchie des filtres sur le site : admin_page doit donc aussi etre activé"})
     */
    private $superadminPage = '0';

    /**
     * @var bool
     *
     * @ORM\Column(name="vis_page", type="boolean", nullable=false, options={"default": "1", "comment": "On / Off"})
     */
    private $visPage = true;

    /**
     * @var int
     *
     * @ORM\Column(name="ordre_page", type="integer", nullable=false)
     */
    private $ordrePage;

    /**
     * @var bool
     *
     * @ORM\Column(name="menu_page", type="boolean", nullable=false, options={"comment": "Apparait dans le menu principal ?"})
     */
    private $menuPage;

    /**
     * @var int
     *
     * @ORM\Column(name="ordre_menu_page", type="integer", nullable=false, options={"comment": "Position dans le menu ppal"})
     */
    private $ordreMenuPage;

    /**
     * @var bool
     *
     * @ORM\Column(name="menuadmin_page", type="boolean", nullable=false, options={"comment": "Apparait dans le menu admin ?"})
     */
    private $menuadminPage;

    /**
     * @var int
     *
     * @ORM\Column(name="ordre_menuadmin_page", type="integer", nullable=false, options={"comment": "Position dans le menu admin"})
     */
    private $ordreMenuadminPage;

    /**
     * @var string
     *
     * @ORM\Column(name="code_page", type="string", length=50, nullable=false, options={"comment": "ID lié au nom des fichiers et des variables"})
     */
    private $codePage;

    /**
     * @var string
     *
     * @ORM\Column(name="default_name_page", type="string", length=100, nullable=false, options={"comment": "Pour les pages admin notamment"})
     */
    private $defaultNamePage;

    /**
     * @var bool
     *
     * @ORM\Column(name="meta_title_page", type="boolean", nullable=false, options={"comment": "Booléen : utiliser un titre sur mesure ou pas"})
     */
    private $metaTitlePage = '0';

    /**
     * @var bool
     *
     * @ORM\Column(name="meta_description_page", type="boolean", nullable=false, options={"comment": "Booléen : utiliser une description sur mesure ou pas"})
     */
    private $metaDescriptionPage = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="priority_page", type="decimal", precision=1, scale=1, nullable=false, options={"comment": "Priorité de sitemap"})
     */
    private $priorityPage;

    /**
     * @var string
     *
     * @ORM\Column(name="add_css_page", type="string", length=200, nullable=false, options={"comment": "Liste de fichiers css à ajouter, séparés par ;"})
     */
    private $addCssPage;

    /**
     * @var string
     *
     * @ORM\Column(name="add_js_page", type="string", length=200, nullable=false, options={"comment": "Liste de fichiers js à ajouter, séparés par ;"})
     */
    private $addJsPage;

    /**
     * @var bool
     *
     * @ORM\Column(name="lock_page", type="boolean", nullable=false, options={"comment": "Bloquer l-édition même au superadmin"})
     */
    private $lockPage;

    /**
     * @var bool
     *
     * @ORM\Column(name="pagelibre_page", type="boolean", nullable=false, options={"comment": "Pour le module de créatino de pages libres. Pour les pages standarts, comme des articles Wordpress"})
     */
    private $pagelibrePage = '0';

    /**
     * @var int
     *
     * @ORM\Column(name="created_page", type="bigint", nullable=false)
     */
    private $createdPage;

    public function getIdPage(): ?int
    {
        return $this->idPage;
    }

    public function getParentPage(): ?int
    {
        return $this->parentPage;
    }

    public function setParentPage(int $parentPage): self
    {
        $this->parentPage = $parentPage;

        return $this;
    }

    public function getAdminPage(): ?bool
    {
        return $this->adminPage;
    }

    public function setAdminPage(bool $adminPage): self
    {
        $this->adminPage = $adminPage;

        return $this;
    }

    public function getSuperadminPage(): ?bool
    {
        return $this->superadminPage;
    }

    public function setSuperadminPage(bool $superadminPage): self
    {
        $this->superadminPage = $superadminPage;

        return $this;
    }

    public function getVisPage(): ?bool
    {
        return $this->visPage;
    }

    public function setVisPage(bool $visPage): self
    {
        $this->visPage = $visPage;

        return $this;
    }

    public function getOrdrePage(): ?int
    {
        return $this->ordrePage;
    }

    public function setOrdrePage(int $ordrePage): self
    {
        $this->ordrePage = $ordrePage;

        return $this;
    }

    public function getMenuPage(): ?bool
    {
        return $this->menuPage;
    }

    public function setMenuPage(bool $menuPage): self
    {
        $this->menuPage = $menuPage;

        return $this;
    }

    public function getOrdreMenuPage(): ?int
    {
        return $this->ordreMenuPage;
    }

    public function setOrdreMenuPage(int $ordreMenuPage): self
    {
        $this->ordreMenuPage = $ordreMenuPage;

        return $this;
    }

    public function getMenuadminPage(): ?bool
    {
        return $this->menuadminPage;
    }

    public function setMenuadminPage(bool $menuadminPage): self
    {
        $this->menuadminPage = $menuadminPage;

        return $this;
    }

    public function getOrdreMenuadminPage(): ?int
    {
        return $this->ordreMenuadminPage;
    }

    public function setOrdreMenuadminPage(int $ordreMenuadminPage): self
    {
        $this->ordreMenuadminPage = $ordreMenuadminPage;

        return $this;
    }

    public function getCodePage(): ?string
    {
        return $this->codePage;
    }

    public function setCodePage(string $codePage): self
    {
        $this->codePage = $codePage;

        return $this;
    }

    public function getDefaultNamePage(): ?string
    {
        return $this->defaultNamePage;
    }

    public function setDefaultNamePage(string $defaultNamePage): self
    {
        $this->defaultNamePage = $defaultNamePage;

        return $this;
    }

    public function getMetaTitlePage(): ?bool
    {
        return $this->metaTitlePage;
    }

    public function setMetaTitlePage(bool $metaTitlePage): self
    {
        $this->metaTitlePage = $metaTitlePage;

        return $this;
    }

    public function getMetaDescriptionPage(): ?bool
    {
        return $this->metaDescriptionPage;
    }

    public function setMetaDescriptionPage(bool $metaDescriptionPage): self
    {
        $this->metaDescriptionPage = $metaDescriptionPage;

        return $this;
    }

    public function getPriorityPage(): ?string
    {
        return $this->priorityPage;
    }

    public function setPriorityPage(string $priorityPage): self
    {
        $this->priorityPage = $priorityPage;

        return $this;
    }

    public function getAddCssPage(): ?string
    {
        return $this->addCssPage;
    }

    public function setAddCssPage(string $addCssPage): self
    {
        $this->addCssPage = $addCssPage;

        return $this;
    }

    public function getAddJsPage(): ?string
    {
        return $this->addJsPage;
    }

    public function setAddJsPage(string $addJsPage): self
    {
        $this->addJsPage = $addJsPage;

        return $this;
    }

    public function getLockPage(): ?bool
    {
        return $this->lockPage;
    }

    public function setLockPage(bool $lockPage): self
    {
        $this->lockPage = $lockPage;

        return $this;
    }

    public function getPagelibrePage(): ?bool
    {
        return $this->pagelibrePage;
    }

    public function setPagelibrePage(bool $pagelibrePage): self
    {
        $this->pagelibrePage = $pagelibrePage;

        return $this;
    }

    public function getCreatedPage(): ?string
    {
        return $this->createdPage;
    }

    public function setCreatedPage(string $createdPage): self
    {
        $this->createdPage = $createdPage;

        return $this;
    }
}
