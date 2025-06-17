<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\UserRepository;
use App\Utils\EmailAlerts;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;

/**
 * User.
 */
#[ORM\Table(name: 'caf_user')]
#[ORM\Index(name: 'id_user', columns: ['id_user'])]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ApiResource(operations: [], normalizationContext: ['groups' => ['user:read']])]
class User implements UserInterface, PasswordAuthenticatedUserInterface, \JsonSerializable
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\Column(name: 'id_user', type: 'bigint', nullable: false)]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups('user:read')]
    private $id;

    #[ORM\OneToMany(targetEntity: 'UserAttr', mappedBy: 'user', cascade: ['persist'])]
    private $attrs;

    /**
     * @var string
     */
    #[ORM\Column(name: 'email_user', type: 'string', length: 200, nullable: true, unique: true)]
    private $email;

    /**
     * @var string
     */
    #[ORM\Column(name: 'mdp_user', type: 'string', length: 1024, nullable: true)]
    #[Ignore]
    private $mdp;

    /**
     * @var string
     */
    #[ORM\Column(name: 'cafnum_user', type: 'string', length: 20, nullable: true, unique: true, options: ['comment' => 'Numéro de licence'])]

    private $cafnum;

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

    private $firstname;

    /**
     * @var string
     */
    #[ORM\Column(name: 'lastname_user', type: 'string', length: 50, nullable: false)]
    #[Groups('user:read')]

    private $lastname;

    /**
     * @var string
     */
    #[ORM\Column(name: 'nickname_user', type: 'string', length: 20, nullable: false)]
    private $nickname;

    /**
     * @var int
     */
    #[ORM\Column(name: 'created_user', type: 'bigint', nullable: false)]
    private $created;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'birthday_user', type: 'bigint', nullable: true)]
    private $birthday;

    /**
     * @var string
     */
    #[ORM\Column(name: 'tel_user', type: 'string', length: 30, nullable: true)]
    private $tel;

    /**
     * @var string
     */
    #[ORM\Column(name: 'tel2_user', type: 'string', length: 30, nullable: true)]
    private $tel2;

    /**
     * @var string
     */
    #[ORM\Column(name: 'adresse_user', type: 'string', length: 100, nullable: true)]
    private $adresse;

    /**
     * @var string
     */
    #[ORM\Column(name: 'cp_user', type: 'string', length: 10, nullable: true)]
    private $cp;

    /**
     * @var string
     */
    #[ORM\Column(name: 'ville_user', type: 'string', length: 30, nullable: true)]
    private $ville;

    /**
     * @var string
     */
    #[ORM\Column(name: 'pays_user', type: 'string', length: 50, nullable: true)]
    private $pays;

    /**
     * @var string
     */
    #[ORM\Column(name: 'civ_user', type: 'string', length: 10, nullable: true)]
    private $civ;

    /**
     * @var string
     */
    #[ORM\Column(name: 'moreinfo_user', type: 'string', length: 500, nullable: true, options: ['comment' => 'FORMATIONS ?'])]
    private $moreinfo;

    /**
     * @var string
     */
    #[ORM\Column(name: 'auth_contact_user', type: 'string', length: 10, nullable: false, options: ['default' => 'users', 'comment' => 'QUI peut me contacter via formulaire'])]
    private $authContact = 'users';

    /**
     * @var bool
     */
    #[ORM\Column(name: 'valid_user', type: 'boolean', nullable: false, options: ['comment' => "0=l'user n'a pas activé son compte   1=activé    2=bloqué"])]
    private $valid = '0';

    /**
     * @var string
     */
    #[ORM\Column(name: 'cookietoken_user', type: 'string', length: 32, nullable: true)]
    #[Ignore]
    private $cookietoken;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'manuel_user', type: 'boolean', nullable: false, options: ['comment' => 'User créé à la mano sur le site ?'])]
    private $manuelUser = '0';

    /**
     * @var bool
     */
    #[ORM\Column(name: 'nomade_user', type: 'boolean', nullable: false)]
    private $nomade = '0';

    /**
     * @var int
     */
    #[ORM\Column(name: 'nomade_parent_user', type: 'integer', nullable: true, options: ['comment' => "Dans le cas d'un user NOMADE, l'ID de son créateur"])]
    private $nomadeParent;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'date_adhesion_user', type: 'bigint', nullable: true)]
    private $dateAdhesion;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'doit_renouveler_user', type: 'boolean', nullable: false)]
    private $doitRenouveler = '0';

    /**
     * @var bool
     */
    #[ORM\Column(name: 'alerte_renouveler_user', type: 'boolean', nullable: false, options: ['comment' => "Si sur 1 : une alerte s'affiche pour annoncer que l'adhérent doit renouveler sa licence"])]
    private $alerteRenouveler = '0';

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'ts_insert_user', type: 'bigint', nullable: true, options: ['comment' => 'timestamp 1ere insertion'])]
    private $tsInsert;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'ts_update_user', type: 'bigint', nullable: true, options: ['comment' => 'timestamp derniere maj'])]
    private $tsUpdate;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ExpenseReport::class, orphanRemoval: false)]
    private Collection $expenseReports;

    #[ORM\Column(name: 'is_deleted', type: 'boolean', nullable: false, options: ['default' => 0])]
    private bool $isDeleted = false;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $alerts = EmailAlerts::DEFAULT_ALERTS;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $alertSortiePrefix = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $alertArticlePrefix = null;

    #[ORM\Column(name: 'materiel_account_created_at', type: 'datetime', nullable: true, options: ['comment' => 'Date de création du compte sur la plateforme de matériel'])]
    private ?\DateTimeInterface $materielAccountCreatedAt = null;

    public function __construct(?int $id = null)
    {
        $this->attrs = new ArrayCollection();
        $this->created = time();
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
            'created' => $this->getCreated(),
            'birthday' => $this->getBirthday(),
            'tel' => $this->getTel(),
            'tel2' => $this->getTel2(),
            'adresse' => $this->getAdresse(),
            'cp' => $this->getCp(),
            'ville' => $this->getVille(),
            'pays' => $this->getPays(),
            'civ' => $this->getCiv(),
            'moreinfo' => $this->getMoreinfo(),
            'authContact' => $this->getAuthContact(),
            'valid' => $this->getValid(),
            'manuel' => $this->getManuel(),
            'nomade' => $this->getNomade(),
            'nomadeParent' => $this->getNomadeParent(),
            'dateAdhesion' => $this->getDateAdhesion(),
            'doitRenouveler' => $this->getDoitRenouveler(),
            'alerteRenouveler' => $this->getAlerteRenouveler(),
            'tsInsert' => $this->getTsInsert(),
            'tsUpdate' => $this->getTsUpdate(),
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

    public function addAttribute(Usertype $userType, $params = null)
    {
        if ($userType->getLimitedToComm() && null === $params) {
            throw new \InvalidArgumentException('User type is limited to commission.');
        }

        $this->attrs->add(new UserAttr($this, $userType, $params));
    }

    public function getId(): ?int
    {
        return null !== $this->id ? (int) $this->id : null;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getMdp(): ?string
    {
        return $this->mdp;
    }

    public function setMdp(string $mdp): self
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

    public function getCreated(): ?string
    {
        return $this->created;
    }

    public function setCreated(string $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getBirthday(): ?string
    {
        return $this->birthday;
    }

    public function setBirthday(?string $birthday): self
    {
        $this->birthday = $birthday;

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

    public function getAuthContact(): ?string
    {
        return $this->authContact;
    }

    public function setAuthContact(string $authContact): self
    {
        $this->authContact = $authContact;

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

    public function getDateAdhesion(): ?string
    {
        return $this->dateAdhesion;
    }

    public function setDateAdhesion(?string $dateAdhesion): self
    {
        $this->dateAdhesion = $dateAdhesion;

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

    public function getTsInsert(): ?string
    {
        return $this->tsInsert;
    }

    public function setTsInsert(?string $tsInsert): self
    {
        $this->tsInsert = $tsInsert;

        return $this;
    }

    public function getTsUpdate(): ?string
    {
        return $this->tsUpdate;
    }

    public function setTsUpdate(?string $tsUpdate): self
    {
        $this->tsUpdate = $tsUpdate;

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

    public function setAlertSortiePrefix(string $alertSortiePrefix): void
    {
        $this->alertSortiePrefix = $alertSortiePrefix;
    }

    public function getAlertArticlePrefix(): ?string
    {
        return $this->alertArticlePrefix;
    }

    public function setAlertArticlePrefix(string $alertArticlePrefix): void
    {
        $this->alertArticlePrefix = $alertArticlePrefix;
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
}
