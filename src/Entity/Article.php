<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Serializer\Filter\GroupFilter;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Article.
 */
#[ORM\Table(name: 'caf_article')]
#[ORM\Index(columns: ['id_article'], name: 'id_article')]
#[ORM\Entity]
#[Vich\Uploadable]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['article:read', 'media:read', 'article:details', 'user:read']]),
        new GetCollection(normalizationContext: ['groups' => ['article:read', 'media:read', 'user:read']]),
    ],
    order: ['createdAt' => 'DESC'],
    security: "is_granted('ROLE_USER')",
)]
#[ApiFilter(SearchFilter::class, properties: ['commission' => 'exact'])]
#[ApiFilter(BooleanFilter::class, properties: ['une'])]
#[ApiFilter(GroupFilter::class)]
class Article
{
    use TimestampableEntity;

    public const int STATUS_PENDING = 0;
    public const int STATUS_PUBLISHED = 1;
    public const int STATUS_REFUSED = 2;

    /**
     * @var int
     */
    #[ORM\Column(name: 'id_article', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups('article:read')]
    private $id;

    /**
     * @var int
     */
    #[ORM\Column(name: 'status_article', type: 'integer', nullable: false, options: ['comment' => '0=pas vu, 1=valide, 2=refusé'])]
    private $status = '0';

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'status_who_article', referencedColumnName: 'id_user', nullable: true)]
    private ?User $statusWho;

    /**
     * @var int
     */
    #[ORM\Column(name: 'topubly_article', type: 'integer', nullable: false, options: ['comment' => 'Demander la publication ? Ou laisser en standby'])]
    private $topubly;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'lastedit_who', referencedColumnName: 'id_user', nullable: true, options: ['comment' => 'User de la dernière modif'])]
    #[Groups('article:read')]
    private ?User $lastEditWho;

    /**
     * @var User
     */
    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'user_article', referencedColumnName: 'id_user', nullable: false)]
    #[Groups('article:read')]
    private $user;

    /**
     * @var string
     */
    #[ORM\Column(name: 'titre_article', type: 'string', length: 200, nullable: false)]
    #[Groups('article:read')]
    private $titre;

    /**
     * @var string
     */
    #[ORM\Column(name: 'code_article', type: 'string', length: 50, nullable: false, options: ['comment' => 'Pour affichage dans les URL'])]
    #[Groups('article:read')]
    private $code;

    #[ORM\ManyToOne(targetEntity: 'Commission')]
    #[ORM\JoinColumn(name: 'commission_article', referencedColumnName: 'id_commission', nullable: true)]
    #[Groups('article:read')]
    private ?Commission $commission = null;

    #[ORM\ManyToOne(targetEntity: 'Evt', inversedBy: 'articles')]
    #[ORM\JoinColumn(name: 'evt_article', referencedColumnName: 'id_evt', nullable: true)]
    #[Groups('article:read')]
    private ?Evt $evt = null;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'une_article', type: 'boolean', nullable: false, options: ['comment' => 'A la une ?'])]
    #[Groups('article:read')]
    private $une = false;

    /**
     * @var string
     */
    #[ORM\Column(name: 'cont_article', type: 'text', nullable: false)]
    #[Groups('article:details')]
    private $cont;

    /**
     * @var int
     */
    #[ORM\Column(name: 'nb_vues_article', type: 'integer', nullable: false, options: ['default' => 0])]
    #[Groups('article:read')]
    private $nbVues = '0';

    #[ORM\ManyToOne(targetEntity: MediaUpload::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups('media:read')]
    private ?MediaUpload $mediaUpload = null;

    #[ORM\Column(name: 'agree_edito', type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $agreeEdito = false;

    #[ORM\Column(name: 'images_authorized', type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $imagesAuthorized = false;

    #[ORM\Column(name: 'validation_date', type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => 'date de publication de l\'article'])]
    #[Groups('event:read')]
    private ?\DateTimeImmutable $validationDate = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->topubly = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatusWho(): ?User
    {
        return $this->statusWho;
    }

    public function setStatusWho(?User $statusWho): self
    {
        $this->statusWho = $statusWho;

        return $this;
    }

    public function getTopubly(): ?int
    {
        return $this->topubly;
    }

    public function setTopubly(int $topubly): self
    {
        $this->topubly = $topubly;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getCommission(): ?Commission
    {
        return $this->commission;
    }

    public function setCommission(?Commission $commission)
    {
        $this->commission = $commission;

        return $this;
    }

    public function getEvt(): ?Evt
    {
        return $this->evt;
    }

    public function setEvt(?Evt $evt): self
    {
        $this->evt = $evt;

        return $this;
    }

    public function getUne(): ?bool
    {
        return $this->une;
    }

    public function setUne(bool $une): self
    {
        $this->une = $une;

        return $this;
    }

    public function getCont(): ?string
    {
        return $this->cont;
    }

    public function setCont(string $cont): self
    {
        $this->cont = $cont;

        return $this;
    }

    public function getNbVues(): ?int
    {
        return $this->nbVues;
    }

    public function setNbVues(int $nbVues): self
    {
        $this->nbVues = $nbVues;

        return $this;
    }

    public function getMediaUpload(): ?MediaUpload
    {
        return $this->mediaUpload;
    }

    public function setMediaUpload(?MediaUpload $mediaUpload): self
    {
        $this->mediaUpload = $mediaUpload;

        return $this;
    }

    public function getLastEditWho(): ?User
    {
        return $this->lastEditWho;
    }

    public function setLastEditWho(?User $lastEditWho): self
    {
        $this->lastEditWho = $lastEditWho;

        return $this;
    }

    public function isAgreeEdito(): bool
    {
        return $this->agreeEdito;
    }

    public function setAgreeEdito(bool $agreeEdito): self
    {
        $this->agreeEdito = $agreeEdito;

        return $this;
    }

    public function isImagesAuthorized(): bool
    {
        return $this->imagesAuthorized;
    }

    public function setImagesAuthorized(bool $imagesAuthorized): self
    {
        $this->imagesAuthorized = $imagesAuthorized;

        return $this;
    }

    public function getValidationDate(): ?\DateTimeImmutable
    {
        return $this->validationDate;
    }

    public function setValidationDate(?\DateTimeImmutable $validationDate): self
    {
        $this->validationDate = $validationDate;

        return $this;
    }

    public function isPublic(): bool
    {
        return self::STATUS_PUBLISHED === $this->status;
    }
}
