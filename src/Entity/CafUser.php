<?php

namespace App\Entity;

use App\Repository\CafUserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * CafUser.
 *
 * @ORM\Table(name="caf_user")
 * @ORM\Entity(repositoryClass=CafUserRepository::class)
 */
class CafUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_user", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idUser;

    /**
     * @ORM\OneToMany(targetEntity="CafUserAttr", mappedBy="user", cascade={"persist"})
     */
    private $attrs;

    /**
     * @var string
     *
     * @ORM\Column(name="email_user", type="string", length=200, nullable=true, unique=true)
     */
    private $emailUser;

    /**
     * @var string
     *
     * @ORM\Column(name="mdp_user", type="string", length=1024, nullable=true)
     */
    private $mdpUser;

    /**
     * @var string
     *
     * @ORM\Column(name="cafnum_user", type="string", length=20, nullable=true, options={"comment": "Numéro de licence"})
     */
    private $cafnumUser;

    /**
     * @var string
     *
     * @ORM\Column(name="cafnum_parent_user", type="string", length=20, nullable=false, options={"comment": "Filiation : numéro CAF du parent"})
     */
    private $cafnumParentUser;

    /**
     * @var string
     *
     * @ORM\Column(name="firstname_user", type="string", length=50, nullable=false)
     */
    private $firstnameUser;

    /**
     * @var string
     *
     * @ORM\Column(name="lastname_user", type="string", length=50, nullable=false)
     */
    private $lastnameUser;

    /**
     * @var string
     *
     * @ORM\Column(name="nickname_user", type="string", length=20, nullable=false)
     */
    private $nicknameUser;

    /**
     * @var int
     *
     * @ORM\Column(name="created_user", type="bigint", nullable=false)
     */
    private $createdUser;

    /**
     * @var int|null
     *
     * @ORM\Column(name="birthday_user", type="bigint", nullable=true)
     */
    private $birthdayUser;

    /**
     * @var string
     *
     * @ORM\Column(name="tel_user", type="string", length=30, nullable=false)
     */
    private $telUser;

    /**
     * @var string
     *
     * @ORM\Column(name="tel2_user", type="string", length=30, nullable=false)
     */
    private $tel2User;

    /**
     * @var string
     *
     * @ORM\Column(name="adresse_user", type="string", length=100, nullable=false)
     */
    private $adresseUser;

    /**
     * @var string
     *
     * @ORM\Column(name="cp_user", type="string", length=10, nullable=false)
     */
    private $cpUser;

    /**
     * @var string
     *
     * @ORM\Column(name="ville_user", type="string", length=30, nullable=false)
     */
    private $villeUser;

    /**
     * @var string
     *
     * @ORM\Column(name="pays_user", type="string", length=50, nullable=false)
     */
    private $paysUser;

    /**
     * @var string
     *
     * @ORM\Column(name="civ_user", type="string", length=10, nullable=false)
     */
    private $civUser;

    /**
     * @var string
     *
     * @ORM\Column(name="moreinfo_user", type="string", length=500, nullable=false, options={"comment": "FORMATIONS ?"})
     */
    private $moreinfoUser;

    /**
     * @var string
     *
     * @ORM\Column(name="auth_contact_user", type="string", length=10, nullable=false, options={"default": "users", "comment": "QUI peut me contacter via formulaire"})
     */
    private $authContactUser = 'users';

    /**
     * @var bool
     *
     * @ORM\Column(name="valid_user", type="boolean", nullable=false, options={"comment": "0=l'user n'a pas activé son compte   1=activé    2=bloqué"})
     */
    private $validUser = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="cookietoken_user", type="string", length=32, nullable=false)
     */
    private $cookietokenUser;

    /**
     * @var bool
     *
     * @ORM\Column(name="manuel_user", type="boolean", nullable=false, options={"comment": "User créé à la mano sur le site ?"})
     */
    private $manuelUser = '0';

    /**
     * @var bool
     *
     * @ORM\Column(name="nomade_user", type="boolean", nullable=false)
     */
    private $nomadeUser = '0';

    /**
     * @var int
     *
     * @ORM\Column(name="nomade_parent_user", type="integer", nullable=false, options={"comment": "Dans le cas d'un user NOMADE, l'ID de son créateur"})
     */
    private $nomadeParentUser;

    /**
     * @var int|null
     *
     * @ORM\Column(name="date_adhesion_user", type="bigint", nullable=true)
     */
    private $dateAdhesionUser;

    /**
     * @var bool
     *
     * @ORM\Column(name="doit_renouveler_user", type="boolean", nullable=false)
     */
    private $doitRenouvelerUser = '0';

    /**
     * @var bool
     *
     * @ORM\Column(name="alerte_renouveler_user", type="boolean", nullable=false, options={"comment": "Si sur 1 : une alerte s'affiche pour annoncer que l'adhérent doit renouveler sa licence"})
     */
    private $alerteRenouvelerUser = '0';

    /**
     * @var int|null
     *
     * @ORM\Column(name="ts_insert_user", type="bigint", nullable=true, options={"comment": "timestamp 1ere insertion"})
     */
    private $tsInsertUser;

    /**
     * @var int|null
     *
     * @ORM\Column(name="ts_update_user", type="bigint", nullable=true, options={"comment": "timestamp derniere maj"})
     */
    private $tsUpdateUser;

    public function __construct()
    {
        $this->attrs = new ArrayCollection();
        $this->createdUser = time();
    }

    public function getAttributes()
    {
        $attributes = $this
            ->attrs
            ->map(function (CafUserAttr $cafUserAttr) {
                return [
                    'attribute' => $cafUserAttr->getUserType()->getTitleUsertype(),
                    'priority' => $cafUserAttr->getUserType()->getHierarchieUsertype(),
                    'commission' => \array_slice(explode(':', $cafUserAttr->getParamsUserAttr()), -1)[0],
                ];
            })
            ->toArray();
        usort($attributes, fn ($array1, $array2) => $array1['priority'] <=> $array2['priority']);

        return $attributes;
    }

    public function hasAttribute($attribute = null, $commission = null): bool
    {
        if (null === $attribute) {
            return \count($this->attrs) > 0;
        }

        foreach ($this->attrs as $cafUserAttr) {
            if (\in_array($cafUserAttr->getUserType()->getTitleUsertype(), (array) $attribute, true)) {
                if (null === $commission) {
                    return true;
                }
                if ($commission === \array_slice(explode(':', $cafUserAttr->getParamsUserAttr()), -1)[0]) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getIdUser(): ?int
    {
        return null !== $this->idUser ? (int) $this->idUser : null;
    }

    public function getEmailUser(): ?string
    {
        return $this->emailUser;
    }

    public function setEmailUser(string $emailUser): self
    {
        $this->emailUser = $emailUser;

        return $this;
    }

    public function getMdpUser(): ?string
    {
        return $this->mdpUser;
    }

    public function setMdpUser(string $mdpUser): self
    {
        $this->mdpUser = $mdpUser;

        return $this;
    }

    public function getCafnumUser(): ?string
    {
        return $this->cafnumUser;
    }

    public function setCafnumUser(string $cafnumUser): self
    {
        $this->cafnumUser = $cafnumUser;

        return $this;
    }

    public function getCafnumParentUser(): ?string
    {
        return $this->cafnumParentUser;
    }

    public function setCafnumParentUser(string $cafnumParentUser): self
    {
        $this->cafnumParentUser = $cafnumParentUser;

        return $this;
    }

    public function getFirstnameUser(): ?string
    {
        return $this->firstnameUser;
    }

    public function setFirstnameUser(string $firstnameUser): self
    {
        $this->firstnameUser = $firstnameUser;

        return $this;
    }

    public function getLastnameUser(): ?string
    {
        return $this->lastnameUser;
    }

    public function setLastnameUser(string $lastnameUser): self
    {
        $this->lastnameUser = $lastnameUser;

        return $this;
    }

    public function getNicknameUser(): ?string
    {
        return $this->nicknameUser;
    }

    public function setNicknameUser(string $nicknameUser): self
    {
        $this->nicknameUser = $nicknameUser;

        return $this;
    }

    public function getCreatedUser(): ?string
    {
        return $this->createdUser;
    }

    public function setCreatedUser(string $createdUser): self
    {
        $this->createdUser = $createdUser;

        return $this;
    }

    public function getBirthdayUser(): ?string
    {
        return $this->birthdayUser;
    }

    public function setBirthdayUser(?string $birthdayUser): self
    {
        $this->birthdayUser = $birthdayUser;

        return $this;
    }

    public function getTelUser(): ?string
    {
        return $this->telUser;
    }

    public function setTelUser(string $telUser): self
    {
        $this->telUser = $telUser;

        return $this;
    }

    public function getTel2User(): ?string
    {
        return $this->tel2User;
    }

    public function setTel2User(string $tel2User): self
    {
        $this->tel2User = $tel2User;

        return $this;
    }

    public function getAdresseUser(): ?string
    {
        return $this->adresseUser;
    }

    public function setAdresseUser(string $adresseUser): self
    {
        $this->adresseUser = $adresseUser;

        return $this;
    }

    public function getCpUser(): ?string
    {
        return $this->cpUser;
    }

    public function setCpUser(string $cpUser): self
    {
        $this->cpUser = $cpUser;

        return $this;
    }

    public function getVilleUser(): ?string
    {
        return $this->villeUser;
    }

    public function setVilleUser(string $villeUser): self
    {
        $this->villeUser = $villeUser;

        return $this;
    }

    public function getPaysUser(): ?string
    {
        return $this->paysUser;
    }

    public function setPaysUser(string $paysUser): self
    {
        $this->paysUser = $paysUser;

        return $this;
    }

    public function getCivUser(): ?string
    {
        return $this->civUser;
    }

    public function setCivUser(string $civUser): self
    {
        $this->civUser = $civUser;

        return $this;
    }

    public function getMoreinfoUser(): ?string
    {
        return $this->moreinfoUser;
    }

    public function setMoreinfoUser(string $moreinfoUser): self
    {
        $this->moreinfoUser = $moreinfoUser;

        return $this;
    }

    public function getAuthContactUser(): ?string
    {
        return $this->authContactUser;
    }

    public function setAuthContactUser(string $authContactUser): self
    {
        $this->authContactUser = $authContactUser;

        return $this;
    }

    public function getValidUser(): ?bool
    {
        return $this->validUser;
    }

    public function setValidUser(bool $validUser): self
    {
        $this->validUser = $validUser;

        return $this;
    }

    public function getCookietokenUser(): ?string
    {
        return $this->cookietokenUser;
    }

    public function setCookietokenUser(string $cookietokenUser): self
    {
        $this->cookietokenUser = $cookietokenUser;

        return $this;
    }

    public function getManuelUser(): ?bool
    {
        return $this->manuelUser;
    }

    public function setManuelUser(bool $manuelUser): self
    {
        $this->manuelUser = $manuelUser;

        return $this;
    }

    public function getNomadeUser(): ?bool
    {
        return $this->nomadeUser;
    }

    public function setNomadeUser(bool $nomadeUser): self
    {
        $this->nomadeUser = $nomadeUser;

        return $this;
    }

    public function getNomadeParentUser(): ?int
    {
        return $this->nomadeParentUser;
    }

    public function setNomadeParentUser(int $nomadeParentUser): self
    {
        $this->nomadeParentUser = $nomadeParentUser;

        return $this;
    }

    public function getDateAdhesionUser(): ?string
    {
        return $this->dateAdhesionUser;
    }

    public function setDateAdhesionUser(?string $dateAdhesionUser): self
    {
        $this->dateAdhesionUser = $dateAdhesionUser;

        return $this;
    }

    public function getDoitRenouvelerUser(): ?bool
    {
        return $this->doitRenouvelerUser;
    }

    public function setDoitRenouvelerUser(bool $doitRenouvelerUser): self
    {
        $this->doitRenouvelerUser = $doitRenouvelerUser;

        return $this;
    }

    public function getAlerteRenouvelerUser(): ?bool
    {
        return $this->alerteRenouvelerUser;
    }

    public function setAlerteRenouvelerUser(bool $alerteRenouvelerUser): self
    {
        $this->alerteRenouvelerUser = $alerteRenouvelerUser;

        return $this;
    }

    public function getTsInsertUser(): ?string
    {
        return $this->tsInsertUser;
    }

    public function setTsInsertUser(?string $tsInsertUser): self
    {
        $this->tsInsertUser = $tsInsertUser;

        return $this;
    }

    public function getTsUpdateUser(): ?string
    {
        return $this->tsUpdateUser;
    }

    public function setTsUpdateUser(?string $tsUpdateUser): self
    {
        $this->tsUpdateUser = $tsUpdateUser;

        return $this;
    }

    public function getRoles()
    {
        return ['ROLE_USER'];
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->getMdpUser();
    }

    public function setPassword(string $password): self
    {
        $this->setMdpUser($password);

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
    public function eraseCredentials()
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
        return (string) $this->getEmailUser();
    }

    public function getUsername()
    {
        return (string) $this->getEmailUser();
    }
}
