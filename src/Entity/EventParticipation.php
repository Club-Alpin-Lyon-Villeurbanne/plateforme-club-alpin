<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\EventParticipationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * EventParticipation.
 */
#[ORM\Table(name: 'caf_evt_join')]
#[ORM\Entity(repositoryClass: EventParticipationRepository::class)]
#[UniqueEntity(fields: ['evt', 'user'], message: 'Cette participation existe déjà')]
#[ApiResource(
    operations: [],
    graphQlOperations: [],
    normalizationContext: ['groups' => ['eventParticipation:read']],
)]
class EventParticipation implements \JsonSerializable
{
    public const STATUS_NON_CONFIRME = 0;
    public const STATUS_VALIDE = 1;
    public const STATUS_REFUSE = 2;
    public const STATUS_ABSENT = 3;

    public const ROLE_MANUEL = 'manuel';
    public const ROLE_INSCRIT = 'inscrit';
    public const ROLE_ENCADRANT = 'encadrant';
    public const ROLE_STAGIAIRE = 'stagiaire';
    public const ROLE_COENCADRANT = 'coencadrant';
    public const ROLE_BENEVOLE = 'benevole';
    public const ROLES_ENCADREMENT = [
        self::ROLE_ENCADRANT,
        self::ROLE_STAGIAIRE,
        self::ROLE_COENCADRANT,
    ];
    public const ROLES_ENCADREMENT_ETENDU = [
        self::ROLE_ENCADRANT,
        self::ROLE_STAGIAIRE,
        self::ROLE_COENCADRANT,
        self::ROLE_BENEVOLE,
    ];

    /**
     * @var int
     */
    #[ORM\Column(name: 'id_evt_join', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups('eventParticipation:read', 'user:read')]
    private $id;

    /**
     * @var int
     */
    #[ORM\Column(name: 'status_evt_join', type: 'smallint', nullable: false, options: ['comment' => '0=non confirmé - 1=validé - 2=refusé'])]
    #[Groups('eventParticipation:read', 'user:read')]
    private $status = self::STATUS_NON_CONFIRME;

    #[ORM\ManyToOne(targetEntity: 'Evt', inversedBy: 'participations', fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'evt_evt_join', nullable: false, referencedColumnName: 'id_evt', onDelete: 'CASCADE')]
    private ?Evt $evt;

    /**
     * @var User
     */
    #[ORM\ManyToOne(targetEntity: 'User', fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'user_evt_join', nullable: false, referencedColumnName: 'id_user', onDelete: 'CASCADE')]
    #[Groups('eventParticipation:read')]
    private $user;

    /**
     * @var User
     */
    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'affiliant_user_join', referencedColumnName: 'id_user', nullable: true)]
    private $affiliantUserJoin;

    /**
     * @var string
     */
    #[ORM\Column(name: 'role_evt_join', type: 'string', length: 20, nullable: false)]
    #[Groups('eventParticipation:read')]
    private $role;

    /**
     * @var int
     */
    #[ORM\Column(name: 'tsp_evt_join', type: 'bigint', nullable: false)]
    #[Groups('eventParticipation:read')]
    private $tsp;

    /**
     * @var int
     */
    #[ORM\Column(name: 'lastchange_when_evt_join', type: 'bigint', nullable: true, options: ['comment' => 'Quand a été modifié cet élément'])]
    private $lastchangeWhen;

    /**
     * @var User
     */
    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'lastchange_who_evt_join', referencedColumnName: 'id_user', nullable: true)]
    private $lastchangeWho;

    /**
     * @var bool|null
     */
    #[ORM\Column(name: 'is_covoiturage', type: 'boolean', nullable: true)]
    private $isCovoiturage;

    public function __construct(Evt $event, User $user, string $role, int $status)
    {
        $this->evt = $event;
        $this->user = $user;
        $this->role = $role;
        $this->status = $status;
        $this->tsp = time();
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

    public function getTsp(): ?string
    {
        return $this->tsp;
    }

    public function setTsp(string $tsp): self
    {
        $this->tsp = $tsp;

        return $this;
    }

    public function getLastchangeWhen(): ?string
    {
        return $this->lastchangeWhen;
    }

    public function setLastchangeWhen(string $lastchangeWhen): self
    {
        $this->lastchangeWhen = $lastchangeWhen;

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

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->id,
            'evt' => $this->evt->getId(),
            'user' => $this->user->getId(),
            'role' => $this->role,
            'status' => $this->status,
            'isCovoiturage' => $this->isCovoiturage,
        ];
    }
}
