<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'caf_user_attr', options: ['comment' => 'table de lien entre les utilisateurs et les niveaux de droit'])]
#[ORM\Entity]
class UserAttr
{
    public const string VISITEUR = 'visiteur';
    public const string DEVELOPPEUR = 'developpeur';
    public const string ADHERENT = 'adherent';
    public const string REDACTEUR = 'redacteur';
    public const string ENCADRANT = 'encadrant';
    public const string STAGIAIRE = 'stagiaire';
    public const string RESPONSABLE_COMMISSION = 'responsable-commission';
    public const string PRESIDENT = 'president';
    public const string VICE_PRESIDENT = 'vice-president';
    public const string ADMINISTRATEUR = 'administrateur';
    public const string SALARIE = 'salarie';
    public const string BENEVOLE = 'benevole_encadrement';
    public const string COENCADRANT = 'coencadrant';
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

    #[ORM\Column(name: 'id_user_attr', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id;

    #[ORM\ManyToOne(targetEntity: 'User', fetch: 'EAGER', inversedBy: 'attrs')]
    #[ORM\JoinColumn(name: 'user_user_attr', referencedColumnName: 'id_user', nullable: false, onDelete: 'CASCADE', options: ['comment' => 'ID utilisateur'])]
    private User $user;

    #[ORM\ManyToOne(targetEntity: 'Usertype')]
    #[ORM\JoinColumn(name: 'usertype_user_attr', referencedColumnName: 'id_usertype', options: ['comment' => 'ID niveau de droit'])]
    private ?Usertype $userType;

    #[ORM\Column(name: 'params_user_attr', type: 'string', length: 200, nullable: true, options: ['comment' => 'commission sur laquelle ça s\'applique'])]
    private ?string $params;

    #[ORM\Column(name: 'details_user_attr', type: 'string', length: 100, nullable: false, options: ['comment' => 'timestamp ?'])]
    private ?string $details;

    #[ORM\Column(name: 'description_user_attr', type: 'string', length: 255, nullable: true, options: ['comment' => 'commentaire'])]
    private ?string $description;

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

    public function getCode(): ?string
    {
        return $this->getUserType()->getCode();
    }

    public function getTitle(): ?string
    {
        return $this->getUserType()->getTitle();
    }

    public function getPriority(): ?int
    {
        return $this->getUserType()->getHierarchie();
    }

    public function getCommission(): string
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
