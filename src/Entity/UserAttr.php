<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserAttr.
 */
#[ORM\Table(name: 'caf_user_attr')]
#[ORM\Entity]
class UserAttr
{
    public const VISITEUR = 'visiteur';
    public const DEVELOPPEUR = 'developpeur';
    public const ADHERENT = 'adherent';
    public const REDACTEUR = 'redacteur';
    public const ENCADRANT = 'encadrant';
    public const STAGIAIRE = 'stagiaire';
    public const RESPONSABLE_COMMISSION = 'responsable-commission';
    public const PRESIDENT = 'president';
    public const VICE_PRESIDENT = 'vice-president';
    public const ADMINISTRATEUR = 'administrateur';
    public const SALARIE = 'salarie';
    public const BENEVOLE = 'benevole_encadrement';
    public const COENCADRANT = 'coencadrant';
    public const array COMMISSION_RELATED = [
        self::RESPONSABLE_COMMISSION,
        self::BENEVOLE,
        self::ENCADRANT,
        self::STAGIAIRE,
        self::COENCADRANT,
        self::REDACTEUR,
    ];
    public const array SKILLS_LISTING = [
        self::RESPONSABLE_COMMISSION,
        self::BENEVOLE,
        self::ENCADRANT,
        self::STAGIAIRE,
        self::COENCADRANT,
    ];

    /**
     * @var int
     */
    #[ORM\Column(name: 'id_user_attr', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    #[ORM\ManyToOne(targetEntity: 'User', inversedBy: 'attrs', fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'user_user_attr', referencedColumnName: 'id_user', nullable: false, onDelete: 'CASCADE')]
    private $user;

    #[ORM\ManyToOne(targetEntity: 'Usertype')]
    #[ORM\JoinColumn(name: 'usertype_user_attr', referencedColumnName: 'id_usertype')]
    private $userType;

    /**
     * @var string
     */
    #[ORM\Column(name: 'params_user_attr', type: 'string', length: 200, nullable: true)]
    private $params;

    /**
     * @var string
     */
    #[ORM\Column(name: 'details_user_attr', type: 'string', length: 100, nullable: false, options: ['comment' => 'date - de qui... ?'])]
    private $details;

    #[ORM\Column(name: 'description_user_attr', type: 'string', length: 100, nullable: true)]
    private $description;

    public function __construct(User $user, Usertype $userType, $params = null)
    {
        $this->user = $user;
        $this->userType = $userType;
        $this->params = $params;
        $this->details = time();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->user->getId();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getUserType(): ?Usertype
    {
        return $this->userType;
    }

    public function getParams(): ?string
    {
        return $this->params;
    }

    public function setParams(string $params): self
    {
        $this->params = $params;

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(string $details): self
    {
        $this->details = $details;

        return $this;
    }

    public function getCode()
    {
        return $this->getUserType()->getCode();
    }

    public function getTitle()
    {
        return $this->getUserType()->getTitle();
    }

    public function getPriority()
    {
        return $this->getUserType()->getHierarchie();
    }

    public function getCommission()
    {
        return \array_slice(explode(':', $this->getParams()), -1)[0];
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription($description): self
    {
        $this->description = $description;

        return $this;
    }
}
