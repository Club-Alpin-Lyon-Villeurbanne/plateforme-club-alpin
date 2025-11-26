<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Serializer\Filter\GroupFilter;
use App\Repository\UserRepository;
use App\Utils\EmailAlerts;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * User.
 */
#[ORM\Table(name: 'caf_user')]
#[ORM\Index(columns: ['id_user'], name: 'id_user')]
#[ORM\Index(columns: ['is_deleted', 'valid_user', 'doit_renouveler_user', 'nomade_user', 'lastname_user'], name: 'idx_user_admin_listing')]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ApiResource(
    shortName: 'utilisateur',
    operations: [
        new Get(normalizationContext: ['groups' => ['user:read', 'user:details']]),
        new Patch(normalizationContext: ['groups' => ['user:read', 'user:details']], denormalizationContext: ['groups' => ['user:write']]),
    ],
    security: "is_granted('ROLE_USER') and object == user",
)]
#[ApiFilter(GroupFilter::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface, \JsonSerializable
{
    use TimestampableEntity;

    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\Column(name: 'id_user', type: 'bigint', nullable: false)]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['user:read'])]
    private $id;

    #[ORM\OneToMany(targetEntity: 'UserAttr', mappedBy: 'user', cascade: ['persist'])]
    private $attrs;

    /**
     * @var string
     */
    #[ORM\Column(name: 'email_user', type: 'string', length: 200, nullable: true, unique: true)]
    #[Groups('user:read')]
    private $email;

    /**
     * @var string
     */
    #[ORM\Column(name: 'mdp_user', type: 'string', length: 1024, nullable: true)]
    #[Ignore]
    private $mdp;

    #[ORM\Column(name: 'cafnum_user', type: 'string', length: 20, unique: true, nullable: false, options: ['comment' => 'Numéro de licence'])]
    #[Groups('user:details')]
    #[SerializedName('numeroLicence')]
    private string $cafnum;

    /**
     * @var string
     */
    #[ORM\Column(name: 'cafnum_parent_user', type: 'string', length: 20, nullable: true, options: ['comment' => 'Filiation : numéro CAF du parent'])]
    private $cafnumParent;

    /**
     * @var string
     */
    #[ORM\Column(name: 'firstname_user', type: 'string', length: 50, nullable: false)]
    #[Groups('user:read')]
    #[SerializedName('prenom')]
    private $firstname;

    /**
     * @var string
     */
    #[ORM\Column(name: 'lastname_user', type: 'string', length: 50, nullable: false)]
    #[Groups('user:read')]
    #[SerializedName('nom')]
    private $lastname;

    /**
     * @var string
     */
    #[ORM\Column(name: 'nickname_user', type: 'string', length: 20, nullable: false)]
    #[Groups('user:read')]
    #[SerializedName('pseudonyme')]
    private $nickname;

    /**
     * @var string
     */
    #[ORM\Column(name: 'tel_user', type: 'string', length: 100, nullable: true)]
    #[Groups('user:details')]
    #[SerializedName('telephone')]
    private $tel;

    /**
     * @var string
     */
    #[ORM\Column(name: 'tel2_user', type: 'string', length: 100, nullable: true)]
    #[Groups('user:details')]
    #[SerializedName('telephoneSecours')]
    private $tel2;

    /**
     * @var string
     */
    #[ORM\Column(name: 'adresse_user', type: 'string', length: 100, nullable: true)]
    #[Groups('user:details')]
    private $adresse;

    /**
     * @var string
     */
    #[ORM\Column(name: 'cp_user', type: 'string', length: 10, nullable: true)]
    #[Groups('user:details')]
    #[SerializedName('codePostal')]
    private $cp;

    /**
     * @var string
     */
    #[ORM\Column(name: 'ville_user', type: 'string', length: 30, nullable: true)]
    #[Groups('user:details')]
    private $ville;

    /**
     * @var string
     */
    #[ORM\Column(name: 'pays_user', type: 'string', length: 50, nullable: true)]
    #[Groups('user:details')]
    private $pays;

    /**
     * @var string
     */
    #[ORM\Column(name: 'civ_user', type: 'string', length: 10, nullable: true)]
    #[Groups('user:details')]
    #[SerializedName('civilite')]
    private $civ;

    /**
     * @var string
     */
    #[ORM\Column(name: 'moreinfo_user', type: 'string', length: 500, nullable: true, options: ['comment' => 'FORMATIONS ?'])]
    #[Groups('user:details')]
    #[SerializedName('informationsSupplementaires')]
    private $moreinfo;

    #[ORM\Column(name: 'valid_user', type: 'boolean', nullable: false, options: ['comment' => "0=l'user n'a pas activé son compte   1=activé"])]
    private bool $valid = false;

    /**
     * @var string
     */
    #[ORM\Column(name: 'cookietoken_user', type: 'string', length: 32, nullable: true)]
    #[Ignore]
    private $cookietoken;

    #[ORM\Column(name: 'manuel_user', type: 'boolean', nullable: false, options: ['comment' => 'User créé à la mano sur le site ?'])]
    private bool $manuelUser = false;

    #[ORM\Column(name: 'nomade_user', type: 'boolean', nullable: false)]
    private bool $nomade = false;

    /**
     * @var int
     */
    #[ORM\Column(name: 'nomade_parent_user', type: 'integer', nullable: true, options: ['comment' => "Dans le cas d'un user NOMADE, l'ID de son créateur"])]
    private $nomadeParent;

    #[ORM\Column(name: 'doit_renouveler_user', type: 'boolean', nullable: false)]
    private bool $doitRenouveler = false;

    #[ORM\Column(name: 'alerte_renouveler_user', type: 'boolean', nullable: false, options: ['comment' => "Si sur 1 : une alerte s'affiche pour annoncer que l'adhérent doit renouveler sa licence"])]
    private bool $alerteRenouveler = false;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ExpenseReport::class, orphanRemoval: false)]
    private Collection $expenseReports;

    #[ORM\Column(name: 'is_deleted', type: 'boolean', nullable: false, options: ['default' => 0])]
    private bool $isDeleted = false;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['user:details', 'user:write'])]
    private ?array $alerts = EmailAlerts::DEFAULT_ALERTS;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $alertSortiePrefix = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $alertArticlePrefix = null;

    #[ORM\Column(name: 'materiel_account_created_at', type: 'datetime', nullable: true, options: ['comment' => 'Date de création du compte sur la plateforme de matériel'])]
    private ?\DateTimeInterface $materielAccountCreatedAt = null;

    #[ORM\Column(name: 'last_login_date', type: 'datetime', nullable: true, options: ['comment' => 'Date de dernière connexion'])]
    private ?\DateTimeInterface $lastLoginDate = null;

    #[ORM\Column(name: 'birthdate', type: Types::DATE_IMMUTABLE, nullable: true, options: ['comment' => 'Date de naissance'])]
    #[Groups('user:details')]
    #[SerializedName('dateNaissance')]
    private ?\DateTimeInterface $birthdate = null;

    #[ORM\Column(name: 'join_date', type: Types::DATE_IMMUTABLE, nullable: true, options: ['comment' => 'Date adhésion'])]
    #[Groups('user:details')]
    private ?\DateTimeInterface $joinDate = null;

    #[ORM\Column(name: 'radiation_date', type: Types::DATE_IMMUTABLE, nullable: true, options: ['comment' => 'Date de radiation FFCAM'])]
    private ?\DateTimeInterface $radiationDate = null;

    #[ORM\Column(type: Types::STRING, nullable: true, options: ['comment' => 'Motif de radiation FFCAM'])]
    private ?string $radiationReason = null;

    public function __construct(?int $id = null)
    {
        $this->attrs = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        if ($id) {
            $this->id = $id;
        }
    }

    public function __toString()
    {
        return $this->getFullName();
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->getId(),
            'email' => $this->getEmail(),
            'firstname' => $this->getFirstname(),
            'lastname' => $this->getLastname(),
            'nickname' => $this->getNickname(),
            'tel' => $this->getTel(),
            'tel2' => $this->getTel2(),
            'adresse' => $this->getAdresse(),
            'cp' => $this->getCp(),
            'ville' => $this->getVille(),
            'pays' => $this->getPays(),
            'civ' => $this->getCiv(),
            'moreinfo' => $this->getMoreinfo(),
            'valid' => $this->getValid(),
            'manuel' => $this->getManuel(),
            'nomade' => $this->getNomade(),
            'nomadeParent' => $this->getNomadeParent(),
            'doitRenouveler' => $this->getDoitRenouveler(),
            'alerteRenouveler' => $this->getAlerteRenouveler(),
            'createdAt' => $this->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt' => $this->getUpdatedAt()->format('Y-m-d H:i:s'),
            'joinDate' => $this->getJoinDate()?->format('Y-m-d'),
            'birthdate' => $this->getBirthdate()?->format('Y-m-d'),
        ];
    }

    public function getFullName(): string
    {
        return $this->getFirstname() . ' ' . $this->getLastname();
    }

    /** @return UserAttr[] */
    public function getAttributes()
    {
        $attrs = $this->attrs->toArray();

        usort($attrs, fn ($a, $b) => $b->getPriority() <=> $a->getPriority());

        return $attrs;
    }

    public function hasAttribute($attribute = null, $commission = null): bool
    {
        if (null === $attribute) {
            return \count($this->attrs) > 0;
        }

        foreach ($this->attrs as $cafUserAttr) {
            /** @var UserAttr $cafUserAttr */
            if (\in_array($cafUserAttr->getUserType()->getCode(), (array) $attribute, true)) {
                if (null === $commission) {
                    return true;
                }
                if ($commission === \array_slice(explode(':', $cafUserAttr->getParams()), -1)[0]) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getAttribute(?string $attribute = null, ?string $commission = null): ?UserAttr
    {
        if (null === $attribute) {
            return null;
        }

        foreach ($this->attrs as $cafUserAttr) {
            /** @var UserAttr $cafUserAttr */
            if (\in_array($cafUserAttr->getUserType()->getCode(), (array) $attribute, true)) {
                if (null === $commission) {
                    return $cafUserAttr;
                }
                if ($commission === \array_slice(explode(':', $cafUserAttr->getParams()), -1)[0]) {
                    return $cafUserAttr;
                }
            }
        }

        return null;
    }

    public function addAttribute(Usertype $userType, ?string $params = null, ?string $description = null): void
    {
        if ($userType->getLimitedToComm() && null === $params) {
            throw new \InvalidArgumentException('User type is limited to commission.');
        }

        $attr = new UserAttr($this, $userType, $params);
        if (!empty($description)) {
            $attr->setDescription($description);
        }

        // éviter les doublons
        $commissionCode = str_replace('commission:', '', $params);
        if (!$this->getAttribute($userType->getCode(), $commissionCode)) {
            $this->attrs->add($attr);
        }
    }

    public function getId(): ?int
    {
        return null !== $this->id ? (int) $this->id : null;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getMdp(): ?string
    {
        return $this->mdp;
    }

    public function setMdp(?string $mdp): self
    {
        $this->mdp = $mdp;

        return $this;
    }

    public function getCafnum(): ?string
    {
        return $this->cafnum;
    }

    public function setCafnum(string $cafnum): self
    {
        $this->cafnum = $cafnum;

        return $this;
    }

    public function getCafnumParent(): ?string
    {
        return $this->cafnumParent;
    }

    public function setCafnumParent(?string $cafnumParent): self
    {
        $this->cafnumParent = $cafnumParent;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return ucfirst($this->firstname);
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return strtoupper($this->lastname);
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setNickname(string $nickname): self
    {
        $this->nickname = $nickname;

        return $this;
    }

    public function getTel(): ?string
    {
        return $this->tel;
    }

    public function setTel(string $tel): self
    {
        $this->tel = $tel;

        return $this;
    }

    public function getTel2(): ?string
    {
        return $this->tel2;
    }

    public function setTel2(string $tel2): self
    {
        $this->tel2 = $tel2;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): self
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getCp(): ?string
    {
        return $this->cp;
    }

    public function setCp(string $cp): self
    {
        $this->cp = $cp;

        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(string $ville): self
    {
        $this->ville = $ville;

        return $this;
    }

    public function getPays(): ?string
    {
        return $this->pays;
    }

    public function setPays(string $pays): self
    {
        $this->pays = $pays;

        return $this;
    }

    public function getCiv(): ?string
    {
        return $this->civ;
    }

    public function setCiv(string $civ): self
    {
        $this->civ = $civ;

        return $this;
    }

    public function getMoreinfo(): ?string
    {
        return $this->moreinfo;
    }

    public function setMoreinfo(string $moreinfo): self
    {
        $this->moreinfo = $moreinfo;

        return $this;
    }

    public function getValid(): ?bool
    {
        return $this->valid;
    }

    public function setValid(bool $valid): self
    {
        $this->valid = $valid;

        return $this;
    }

    public function getCookietoken(): ?string
    {
        return $this->cookietoken;
    }

    public function setCookietoken(string $cookietoken): self
    {
        $this->cookietoken = $cookietoken;

        return $this;
    }

    public function getManuel(): ?bool
    {
        return $this->manuelUser;
    }

    public function setManuel(bool $manuelUser): self
    {
        $this->manuelUser = $manuelUser;

        return $this;
    }

    public function getNomade(): ?bool
    {
        return $this->nomade;
    }

    public function setNomade(bool $nomade): self
    {
        $this->nomade = $nomade;

        return $this;
    }

    public function getNomadeParent(): ?int
    {
        return $this->nomadeParent;
    }

    public function setNomadeParent(int $nomadeParent): self
    {
        $this->nomadeParent = $nomadeParent;

        return $this;
    }

    public function getDoitRenouveler(): ?bool
    {
        return $this->doitRenouveler;
    }

    public function setDoitRenouveler(bool $doitRenouveler): self
    {
        $this->doitRenouveler = $doitRenouveler;

        return $this;
    }

    public function getAlerteRenouveler(): ?bool
    {
        return $this->alerteRenouveler;
    }

    public function setAlerteRenouveler(bool $alerteRenouveler): self
    {
        $this->alerteRenouveler = $alerteRenouveler;

        return $this;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->getMdp();
    }

    public function setPassword(string $password): self
    {
        $this->setMdp($password);

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->getEmail();
    }

    public function getUsername()
    {
        return (string) $this->getEmail();
    }

    /**
     * Get the value of expenseReports.
     */
    public function getExpenseReports(): Collection
    {
        return $this->expenseReports;
    }

    /**
     * Set the value of expenseReports.
     */
    public function setExpenseReports(Collection $expenseReports): self
    {
        $this->expenseReports = $expenseReports;

        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted)
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    public function getAlerts(): ?array
    {
        return $this->alerts;
    }

    public function setAlerts(?array $alerts): void
    {
        $this->alerts = $alerts;
    }

    public function hasAlertEnabledOn(AlertType $type, string $commissionCode): bool
    {
        return $this->alerts[$commissionCode][$type->name] ?? false;
    }

    public function setAlertStatus(AlertType $type, string $commissionCode, bool $status): self
    {
        $this->alerts[$commissionCode][$type->name] = $status;

        return $this;
    }

    public function getAlertSortiePrefix(): ?string
    {
        return $this->alertSortiePrefix;
    }

    public function setAlertSortiePrefix(?string $alertSortiePrefix): self
    {
        $this->alertSortiePrefix = $alertSortiePrefix;

        return $this;
    }

    public function getAlertArticlePrefix(): ?string
    {
        return $this->alertArticlePrefix;
    }

    public function setAlertArticlePrefix(?string $alertArticlePrefix): self
    {
        $this->alertArticlePrefix = $alertArticlePrefix;

        return $this;
    }

    public function getMaterielAccountCreatedAt(): ?\DateTimeInterface
    {
        return $this->materielAccountCreatedAt;
    }

    public function setMaterielAccountCreatedAt(?\DateTimeInterface $materielAccountCreatedAt): self
    {
        $this->materielAccountCreatedAt = $materielAccountCreatedAt;

        return $this;
    }

    public function hasMaterielAccount(): bool
    {
        return null !== $this->materielAccountCreatedAt;
    }

    public function getLastLoginDate(): ?\DateTimeInterface
    {
        return $this->lastLoginDate;
    }

    public function setLastLoginDate(?\DateTimeInterface $lastLoginDate): self
    {
        $this->lastLoginDate = $lastLoginDate;

        return $this;
    }

    public function getBirthdate(): ?\DateTimeInterface
    {
        return $this->birthdate;
    }

    public function setBirthdate(?\DateTimeInterface $birthdate): self
    {
        $this->birthdate = $birthdate;

        return $this;
    }

    public function getJoinDate(): ?\DateTimeInterface
    {
        return $this->joinDate;
    }

    public function setJoinDate(?\DateTimeInterface $joinDate): self
    {
        $this->joinDate = $joinDate;

        return $this;
    }

    public function getRadiationDate(): ?\DateTimeInterface
    {
        return $this->radiationDate;
    }

    public function setRadiationDate(?\DateTimeInterface $radiationDate): self
    {
        $this->radiationDate = $radiationDate;

        return $this;
    }

    public function getRadiationReason(): ?string
    {
        return $this->radiationReason;
    }

    public function setRadiationReason(?string $radiationReason): self
    {
        $this->radiationReason = $radiationReason;

        return $this;
    }
}
