<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\EventParticipationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[ORM\Table(name: 'caf_evt_join')]
#[ORM\Entity(repositoryClass: EventParticipationRepository::class)]
#[UniqueEntity(fields: ['evt', 'user'], message: 'Cette participation existe déjà')]
#[ApiResource(
    shortName: 'participation-sortie',
    operations: [
        new GetCollection(),
        new Get(),
    ],
    normalizationContext: ['groups' => ['eventParticipation:read', 'user:read']],
    security: "is_granted('ROLE_USER')",
)]
class EventParticipation implements \JsonSerializable
{
    use TimestampableEntity;

    public const int STATUS_NON_CONFIRME = 0;
    public const int STATUS_VALIDE = 1;
    public const int STATUS_REFUSE = 2;
    public const int STATUS_ABSENT = 3;

    public const string ROLE_MANUEL = 'manuel';
    public const string ROLE_INSCRIT = 'inscrit';
    public const string BENEVOLE = 'benevole';
    public const string ROLE_ENCADRANT = 'encadrant';
    public const string ROLE_STAGIAIRE = 'stagiaire';
    public const string ROLE_COENCADRANT = 'coencadrant';
    public const string ROLE_BENEVOLE = 'benevole_encadrement';
    public const array ROLES_ENCADREMENT = [
        self::ROLE_ENCADRANT,
        self::ROLE_STAGIAIRE,
        self::ROLE_COENCADRANT,
    ];
    public const array ROLES_ENCADREMENT_ETENDU = [
        self::ROLE_ENCADRANT,
        self::ROLE_STAGIAIRE,
        self::ROLE_COENCADRANT,
        self::ROLE_BENEVOLE,
    ];
    public const array ROLES_SIMPLES = [
        self::ROLE_INSCRIT,
        self::ROLE_MANUEL,
    ];

    #[ORM\Column(name: 'id_evt_join', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['eventParticipation:read', 'user:read'])]
    private int $id;

    #[ORM\Column(name: 'status_evt_join', type: 'smallint', nullable: false, options: ['comment' => '0=non confirmé - 1=validé - 2=refusé'])]
    #[Groups(['eventParticipation:read', 'user:read'])]
    #[SerializedName('statut')]
    private int $status = self::STATUS_NON_CONFIRME;

    #[ORM\ManyToOne(targetEntity: 'Evt', fetch: 'EAGER', inversedBy: 'participations')]
    #[ORM\JoinColumn(name: 'evt_evt_join', referencedColumnName: 'id_evt', nullable: false, onDelete: 'CASCADE', options: ['comment' => 'ID sortie'])]
    private ?Evt $evt;

    #[ORM\ManyToOne(targetEntity: 'User', fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'user_evt_join', referencedColumnName: 'id_user', nullable: false, onDelete: 'CASCADE', options: ['comment' => 'ID utilisateur'])]
    #[Groups('eventParticipation:read')]
    #[SerializedName('utilisateur')]
    private User|UserInterface $user;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'affiliant_user_join', referencedColumnName: 'id_user', nullable: true, options: ['comment' => 'plus utilisé ?'])]
    private ?User $affiliantUserJoin;

    #[ORM\Column(name: 'role_evt_join', type: 'string', length: 20, nullable: false, options: ['comment' => 'rôle utilisateur'])]
    #[Groups('eventParticipation:read')]
    private string $role;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'lastchange_who_evt_join', referencedColumnName: 'id_user', nullable: true, options: ['comment' => "ID de l'utilisateur qui a accepté ou refusé la participation"])]
    private ?User $lastchangeWho;

    #[ORM\Column(name: 'is_covoiturage', type: 'boolean', nullable: true, options: ['comment' => 'plus utilisé'])]
    #[SerializedName('proposeCovoiturage')]
    private ?bool $isCovoiturage;

    #[ORM\Column(name: 'has_paid', type: Types::BOOLEAN, nullable: false, options: ['default' => false, 'comment' => 'a payé sur HelloAsso (si applicable et que l\'email était OK)'])]
    private bool $hasPaid = false;

    public function __construct(Evt $event, UserInterface $user, string $role, int $status)
    {
        $this->evt = $event;
        $this->user = $user;
        $this->role = $role;
        $this->status = $status;
        $this->hasPaid = false;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
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

    public function isStatusEnAttente()
    {
        return self::STATUS_NON_CONFIRME === $this->status;
    }

    public function isStatusValide()
    {
        return self::STATUS_VALIDE === $this->status;
    }

    public function isStatusRefuse()
    {
        return self::STATUS_REFUSE === $this->status;
    }

    public function isStatusAbsent()
    {
        return self::STATUS_ABSENT === $this->status;
    }

    public function isRoleManuel()
    {
        return self::ROLE_MANUEL === $this->role;
    }

    public function isRoleInscrit()
    {
        return self::ROLE_INSCRIT === $this->role;
    }

    public function isRoleEncadrant()
    {
        return self::ROLE_ENCADRANT === $this->role;
    }

    public function isRoleStagiaire()
    {
        return self::ROLE_STAGIAIRE === $this->role;
    }

    public function isRoleCoencadrant()
    {
        return self::ROLE_COENCADRANT === $this->role;
    }

    public function isRoleBenevole()
    {
        return self::ROLE_BENEVOLE === $this->role;
    }

    public function isBenevole()
    {
        return self::BENEVOLE === $this->role;
    }

    public function getEvt(): ?Evt
    {
        return $this->evt;
    }

    public function getEvent(): ?Evt
    {
        return $this->evt;
    }

    public function setEvt(?Evt $evt): self
    {
        $this->evt = $evt;

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

    public function getAffiliantUserJoin(): ?User
    {
        return $this->affiliantUserJoin;
    }

    public function setAffiliantUserJoin(User $affiliantUserJoin): self
    {
        $this->affiliantUserJoin = $affiliantUserJoin;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getLastchangeWho(): ?User
    {
        return $this->lastchangeWho;
    }

    public function setLastchangeWho(User $lastchangeWho): self
    {
        $this->lastchangeWho = $lastchangeWho;

        return $this;
    }

    public function getIsCovoiturage(): ?bool
    {
        return $this->isCovoiturage;
    }

    public function setIsCovoiturage(?bool $isCovoiturage): self
    {
        $this->isCovoiturage = $isCovoiturage;

        return $this;
    }

    public function hasPaid(): bool
    {
        return $this->hasPaid;
    }

    public function setHasPaid(bool $hasPaid): self
    {
        $this->hasPaid = $hasPaid;

        return $this;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->id,
            'evt' => $this->evt->getId(),
            'user' => $this->user->getId(),
            'role' => $this->role,
            'status' => $this->status,
            'isCovoiturage' => $this->isCovoiturage,
            'hasPaid' => $this->hasPaid,
        ];
    }
}
