<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Serializer\Filter\GroupFilter;
use App\Serializer\TimeStamp;
use App\Serializer\TimeStampNormalizer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Attribute\Context;

/**
 * Evt.
 */
#[ORM\Table(name: 'caf_evt')]
#[ORM\Entity]
#[ApiResource(
    order: ['tsp' => 'ASC'],
    operations: [new Get(), new GetCollection()],
    normalizationContext: ['groups' => ['event:read']],
    graphQlOperations: [
        new Query(normalizationContext: ['groups' => ['event:read', 'commission:read']]),
        new QueryCollection(normalizationContext: ['groups' => ['event:read', 'commission:read']])
    ],
    security: "is_granted('ROLE_USER')",
)]
#[ApiFilter(SearchFilter::class, properties: ['commission' => 'exact'])]
#[ApiFilter(RangeFilter::class, properties: ['tsp'])]
#[ApiFilter(GroupFilter::class)]
class Evt
{
    public const STATUS_PUBLISHED_UNSEEN = 0;
    public const STATUS_PUBLISHED_VALIDE = 1;
    public const STATUS_PUBLISHED_REFUSE = 2;

    public const STATUS_LEGAL_UNSEEN = 0;
    public const STATUS_LEGAL_VALIDE = 1;
    public const STATUS_LEGAL_REFUSE = 2;

    #[ORM\Column(name: 'id_evt', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups('event:read')]
    private ?int $id;

    #[ORM\Column(name: 'status_evt', type: 'smallint', nullable: false, options: ['comment' => '0-unseen 1-ok 2-refused', 'default' => 0])]
    #[Groups('event:read')]
    private int $status = 0;

    #[ORM\Column(name: 'auto_accept', type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $autoAccept = false;

    #[ORM\Column(name: 'is_draft', type: 'boolean', nullable: false, options: ['default' => true])]
    private bool $isDraft = true;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'status_who_evt', referencedColumnName: 'id_user', nullable: true)]
    private ?User $statusWho;

    #[ORM\Column(name: 'status_legal_evt', type: 'smallint', nullable: false, options: ['comment' => '0-unseen 1-ok 2-refused', 'default' => 0])]
    #[Groups('event:read')]
    private int $statusLegal = 0;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'status_legal_who_evt', referencedColumnName: 'id_user', nullable: true)]
    private ?User $statusLegalWho;

    #[ORM\Column(name: 'cancelled_evt', type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $cancelled = false;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'cancelled_who_evt', referencedColumnName: 'id_user', nullable: true)]
    private ?User $cancelledWho;

    #[ORM\Column(name: 'cancelled_when_evt', type: 'bigint', nullable: true, options: ['comment' => 'Timestamp annulation'])]
    private ?int $cancelledWhen;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: ExpenseReport::class)]
    private ?Collection $expenseReports;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'user_evt', referencedColumnName: 'id_user', nullable: false)]
    #[Groups('event:read')]
    private ?User $user;

    #[ORM\ManyToOne(targetEntity: 'Commission')]
    #[ORM\JoinColumn(name: 'commission_evt', referencedColumnName: 'id_commission', nullable: false)]
    #[Groups('event:read')]
    private ?Commission $commission;

    #[ORM\ManyToOne(targetEntity: 'Groupe', fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'id_groupe', referencedColumnName: 'id', nullable: true)]
    private ?Groupe $groupe;

    #[ORM\Column(name: 'tsp_evt', type: 'bigint', nullable: true, options: ['comment' => 'timestamp du début du event'])]
    #[Groups('event:read')]
    #[Context(normalizationContext:[TimeStampNormalizer::FORMAT_KEY => 'Y-m-d H:i:s'])]
    #[TimeStamp]
    private ?int $tsp;

    #[ORM\Column(name: 'tsp_end_evt', type: 'bigint', nullable: true)]
    #[Groups('event:read')]
    #[Context(normalizationContext:[TimeStampNormalizer::FORMAT_KEY => 'Y-m-d H:i:s'])]
    #[TimeStamp]
    private ?int $tspEnd;

    #[ORM\Column(name: 'tsp_crea_evt', type: 'bigint', nullable: false, options: ['comment' => "Création de l'entrée"])]
    #[Context(normalizationContext:[TimeStampNormalizer::FORMAT_KEY => 'Y-m-d H:i:s'])]
    #[TimeStamp]
    private ?int $tspCrea;

    #[ORM\Column(name: 'tsp_edit_evt', type: 'bigint', nullable: true)]
    #[Context(normalizationContext:[TimeStampNormalizer::FORMAT_KEY => 'Y-m-d H:i:s'])]
    #[TimeStamp]
    private ?int $tspEdit;

    #[ORM\Column(name: 'place_evt', type: 'string', length: 100, nullable: false, options: ['comment' => 'Lieu de départ activité'])]
    private ?string $place;

    #[ORM\Column(name: 'titre_evt', type: 'string', length: 100, nullable: false)]
    #[Groups('event:read')]
    private ?string $titre;

    #[ORM\Column(name: 'code_evt', type: 'string', length: 30, nullable: false)]
    #[Groups('event:read')]
    private ?string $code;

    #[ORM\Column(name: 'massif_evt', type: 'string', length: 100, nullable: true)]
    #[Groups('event:details')]
    private ?string $massif;

    #[ORM\Column(name: 'rdv_evt', type: 'string', length: 200, nullable: false, options: ['comment' => 'Lieu de RDV covoiturage'])]
    #[Groups('event:read')]
    private ?string $rdv;

    #[ORM\Column(name: 'tarif_evt', type: 'float', precision: 10, scale: 2, nullable: true)]
    #[Groups('event:details')]
    private ?float $tarif;

    #[ORM\Column(name: 'tarif_detail', type: 'text', nullable: true)]
    #[Groups('event:details')]
    private ?string $tarifDetail;

    #[ORM\Column(name: 'denivele_evt', type: 'text', nullable: true)]
    #[Groups('event:details')]
    private ?string $denivele;

    #[ORM\Column(name: 'distance_evt', type: 'text', nullable: true)]
    #[Groups('event:details')]
    private ?string $distance;

    #[ORM\Column(name: 'lat_evt', type: 'decimal', precision: 11, scale: 8, nullable: false)]
    #[Groups('event:details')]
    private string|float|null $lat;

    #[ORM\Column(name: 'long_evt', type: 'decimal', precision: 11, scale: 8, nullable: false)]
    #[Groups('event:details')]
    private string|float|null $long;

    #[ORM\Column(name: 'matos_evt', type: 'text', nullable: true)]
    #[Groups('event:details')]
    private ?string $matos;

    #[ORM\Column(name: 'difficulte_evt', type: 'string', length: 50, nullable: true)]
    #[Groups('event:read')]
    private ?string $difficulte;

    #[ORM\Column(name: 'itineraire', type: 'text', nullable: true)]
    #[Groups('event:details')]
    private ?string $itineraire;

    #[ORM\Column(name: 'description_evt', type: 'text', nullable: false)]
    #[Groups('event:read')]
    private ?string $description;

    #[ORM\Column(name: 'need_benevoles_evt', type: 'boolean', nullable: false)]
    #[Groups('event:details')]
    private bool $needBenevoles = false;

    #[ORM\Column(name: 'join_start_evt', type: 'integer', nullable: true, options: ['comment' => 'Timestamp de départ des inscriptions'])]
    #[Context(normalizationContext:[TimeStampNormalizer::FORMAT_KEY => 'Y-m-d H:i:s'])]
    #[TimeStamp]
    #[Groups('event:details')]
    private ?int $joinStart;

    #[ORM\Column(name: 'join_max_evt', type: 'integer', nullable: false, options: ['comment' => "Nombre max d'inscriptions spontanées sur le site, ET PAS d'inscrits total"])]
    #[Groups('event:details')]
    private ?int $joinMax;

    #[ORM\OneToMany(mappedBy: 'evt', targetEntity: 'EventParticipation', cascade: ['persist'], orphanRemoval: true)]
    private ?Collection $participations;

    #[ORM\Column(name: 'ngens_max_evt', type: 'integer', nullable: false, options: ['comment' => 'Nombre de gens pouvant y aller au total. Donnée "visuelle" uniquement, pas de calcul.'])]
    #[Groups('event:details')]
    private ?int $ngensMax;

    #[ORM\OneToMany(mappedBy: 'evt', targetEntity: 'App\Entity\Article')]
    private ?Collection $articles;

    #[ORM\Column(name: 'details_caches_evt', type: 'text', nullable: true)]
    private ?string $detailsCaches;

    public function __construct(
        ?User $user,
        ?Commission $commission,
        ?string $titre,
        ?string $code,
        ?\DateTime $dateStart,
        ?\DateTime $dateEnd,
        ?string $rdv,
        ?float $rdvLat,
        ?float $rdvLong,
        ?string $description,
        ?int $demarrageInscriptions,
        ?int $maxInscriptions,
        ?int $maxParticipants
    ) {
        $this->user = $user;
        $this->titre = $titre;
        $this->code = $code;
        $this->tsp = $dateStart ? $dateStart->getTimestamp() : null;
        $this->tspEnd = $dateEnd ? $dateEnd->getTimestamp() : null;
        $this->place = ''; // unused, must be dropped
        $this->rdv = $rdv;
        $this->lat = $rdvLat;
        $this->long = $rdvLong;
        $this->description = $description;
        $this->joinStart = $demarrageInscriptions;
        $this->joinMax = $maxInscriptions;
        $this->ngensMax = $maxParticipants;
        $this->commission = $commission;
        $this->difficulte = null;
        $this->groupe = null;
        $this->tarif = null;
        $this->tarifDetail = null;
        $this->denivele = null;
        $this->distance = null;
        $this->matos = null;
        $this->itineraire = null;
        $this->detailsCaches = null;
        $this->massif = null;
        $this->participations = new ArrayCollection();
        $this->articles = new ArrayCollection();
        $this->expenseReports = new ArrayCollection();
        $this->tspCrea = time();
        $this->tspEdit = time();
        $this->isDraft = false;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->id,
            'user' => $this->user->getId(),
            'titre' => $this->titre,
            'code' => $this->code,
            'tsp' => $this->tsp,
            'tspEnd' => $this->tspEnd,
            'place' => $this->place,
            'rdv' => $this->rdv,
            'lat' => $this->lat,
            'long' => $this->long,
            'description' => $this->description,
            'joinStart' => $this->joinStart,
            'joinMax' => $this->joinMax,
            'ngensMax' => $this->ngensMax,
            'commission' => $this->commission->getId(),
            'participations' => $this->participations,
            'articles' => $this->articles,
            'tspCrea' => $this->tspCrea,
        ];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getArticles(): Collection
    {
        return $this->articles;
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

    public function setStatusWho(User $statusWho): self
    {
        $this->statusWho = $statusWho;

        return $this;
    }

    public function getStatusLegal(): ?int
    {
        return $this->statusLegal;
    }

    public function setStatusLegal(int $statusLegal): self
    {
        $this->statusLegal = $statusLegal;

        return $this;
    }

    public function getStatusLegalWho(): ?User
    {
        return $this->statusLegalWho;
    }

    public function setStatusLegalWho(User $statusLegalWho): self
    {
        $this->statusLegalWho = $statusLegalWho;

        return $this;
    }

    public function getCancelled(): ?bool
    {
        return $this->cancelled;
    }

    public function setCancelled(bool $cancelled): self
    {
        $this->cancelled = $cancelled;

        return $this;
    }

    public function getCancelledWho(): ?User
    {
        return $this->cancelledWho;
    }

    public function setCancelledWho(?User $cancelledWho): self
    {
        $this->cancelledWho = $cancelledWho;

        return $this;
    }

    public function getCancelledWhen(): ?int
    {
        return $this->cancelledWhen;
    }

    public function setCancelledWhen(?int $cancelledWhen): self
    {
        $this->cancelledWhen = $cancelledWhen;

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

    public function addParticipation(User $user, string $role = EventParticipation::ROLE_INSCRIT, int $status = EventParticipation::STATUS_NON_CONFIRME): EventParticipation
    {
        $participation = new EventParticipation($this, $user, $role, $status);
        $this->participations->add($participation);

        return $participation;
    }

    public function removeParticipation(EventParticipation $participation): void
    {
        if ($this->participations->removeElement($participation)) {
            $participation->setEvt(null);
        }
    }

    /** @return EventParticipation[] */
    public function getParticipations($roles = null, $status = EventParticipation::STATUS_VALIDE): Collection
    {
        if (null !== $roles && !\is_array($roles)) {
            $roles = (array) $roles;
        }
        if (null !== $status && !\is_array($status)) {
            $status = (array) $status;
        }

        return $this->participations->filter(function (EventParticipation $participation) use ($roles, $status) {
            return (null === $roles || \in_array($participation->getRole(), $roles, true))
                && (null === $status || \in_array($participation->getStatus(), $status, true));
        });
    }

    public function clearRoleParticipations(string $role = EventParticipation::ROLE_BENEVOLE): void
    {
        $participations = $this->getParticipations([$role], null);
        foreach ($participations as $participation) {
            $this->removeParticipation($participation);
        }
    }

    #[Groups('event:read')]
    public function getParticipationsCount(): int
    {
        $participations = $this->getParticipations();

        return \count($participations);
    }

    public function getParticipation(?User $user): ?EventParticipation
    {
        if (!$user) {
            return null;
        }

        foreach ($this->participations as $participation) {
            if ($participation->getUser() === $user) {
                return $participation;
            }
        }

        return null;
    }

    public function getParticipationById(int $id): ?EventParticipation
    {
        foreach ($this->participations as $participation) {
            if ($participation->getId() === $id) {
                return $participation;
            }
        }

        return null;
    }

    /** @return EventParticipation[] */
    public function getEncadrants($types = [EventParticipation::ROLE_ENCADRANT, EventParticipation::ROLE_STAGIAIRE, EventParticipation::ROLE_COENCADRANT]): Collection
    {
        return $this->getParticipations($types, [EventParticipation::STATUS_VALIDE]);
    }

    public function getCommission(): ?Commission
    {
        return $this->commission;
    }

    public function setCommission(?Commission $commission): self
    {
        $this->commission = $commission;

        return $this;
    }

    public function getGroupe(): ?Groupe
    {
        return $this->groupe;
    }

    public function setGroupe(?Groupe $groupe): self
    {
        $this->groupe = $groupe;

        return $this;
    }

    public function getTsp(): ?int
    {
        return $this->tsp;
    }

    public function setTsp(int $tsp): self
    {
        $this->tsp = $tsp;

        return $this;
    }

    public function isPublicStatusUnseen()
    {
        return self::STATUS_PUBLISHED_UNSEEN === $this->status;
    }

    public function isPublicStatusValide()
    {
        return self::STATUS_PUBLISHED_VALIDE === $this->status;
    }

    public function isPublicStatusRefuse()
    {
        return self::STATUS_PUBLISHED_REFUSE === $this->status;
    }

    public function isLegalStatusUnseen()
    {
        return self::STATUS_LEGAL_UNSEEN === $this->statusLegal;
    }

    public function isLegalStatusValide()
    {
        return self::STATUS_LEGAL_VALIDE === $this->statusLegal;
    }

    public function isLegalStatusRefuse()
    {
        return self::STATUS_LEGAL_REFUSE === $this->statusLegal;
    }

    public function hasStarted(): bool
    {
        return $this->tsp < time();
    }

    public function startsAfter(string $when): bool
    {
        return $this->tsp > strtotime($when);
    }

    public function joinHasStarted(): bool
    {
        return $this->joinStart < time();
    }

    public function isFinished(): bool
    {
        return $this->tspEnd < time();
    }

    public function getTspEnd(): ?string
    {
        return $this->tspEnd;
    }

    public function setTspEnd(string $tspEnd): self
    {
        $this->tspEnd = $tspEnd;

        return $this;
    }

    public function getTspCrea(): ?string
    {
        return $this->tspCrea;
    }

    public function setTspCrea(string $tspCrea): self
    {
        $this->tspCrea = $tspCrea;

        return $this;
    }

    public function getTspEdit(): ?string
    {
        return $this->tspEdit;
    }

    public function setTspEdit(string $tspEdit): self
    {
        $this->tspEdit = $tspEdit;

        return $this;
    }

    public function getPlace(): ?string
    {
        return $this->place;
    }

    public function setPlace(string $place): self
    {
        $this->place = $place;

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

    public function getMassif(): ?string
    {
        return $this->massif;
    }

    public function setMassif(?string $massif): self
    {
        $this->massif = $massif;

        return $this;
    }

    public function getRdv(): ?string
    {
        return $this->rdv;
    }

    public function setRdv(?string $rdv): self
    {
        $this->rdv = $rdv;

        return $this;
    }

    public function getTarif(): ?float
    {
        return $this->tarif;
    }

    public function setTarif(?float $tarif): self
    {
        $this->tarif = $tarif;

        return $this;
    }

    public function getTarifDetail(): ?string
    {
        return $this->tarifDetail;
    }

    public function setTarifDetail(?string $tarifDetail): self
    {
        $this->tarifDetail = $tarifDetail;

        return $this;
    }

    public function getDenivele(): ?string
    {
        return $this->denivele;
    }

    public function setDenivele(?string $denivele): self
    {
        $this->denivele = $denivele;

        return $this;
    }

    public function getDistance(): ?string
    {
        return $this->distance;
    }

    public function setDistance(?string $distance): self
    {
        $this->distance = $distance;

        return $this;
    }

    public function getLat(): string|float|null
    {
        return $this->lat;
    }

    public function setLat(string|float|null $lat): self
    {
        $this->lat = $lat;

        return $this;
    }

    public function getLong(): string|float|null
    {
        return $this->long;
    }

    public function setLong(string|float|null $long): self
    {
        $this->long = $long;

        return $this;
    }

    public function getMatos(): ?string
    {
        return $this->matos;
    }

    public function setMatos(?string $matos): self
    {
        $this->matos = $matos;

        return $this;
    }

    public function getDifficulte(): ?string
    {
        return $this->difficulte;
    }

    public function setDifficulte(?string $difficulte): self
    {
        $this->difficulte = $difficulte;

        return $this;
    }

    public function getItineraire(): ?string
    {
        return $this->itineraire;
    }

    public function setItineraire(?string $itineraire): self
    {
        $this->itineraire = $itineraire;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getNeedBenevoles(): ?bool
    {
        return $this->needBenevoles;
    }

    public function setNeedBenevoles(bool $needBenevoles): self
    {
        $this->needBenevoles = $needBenevoles;

        return $this;
    }

    public function getJoinStart(): ?int
    {
        return $this->joinStart;
    }

    public function setJoinStart(?int $joinStart): self
    {
        $this->joinStart = $joinStart;

        return $this;
    }

    public function getJoinMax(): ?int
    {
        return $this->joinMax;
    }

    public function setJoinMax(int $joinMax): self
    {
        $this->joinMax = $joinMax;

        return $this;
    }

    public function getNgensMax(): ?int
    {
        return $this->ngensMax;
    }

    public function setNgensMax(int $ngensMax): self
    {
        $this->ngensMax = $ngensMax;

        return $this;
    }

    public function getDetailsCaches(): ?string
    {
        return $this->detailsCaches;
    }

    public function setDetailsCaches(?string $detailsCaches): self
    {
        $this->detailsCaches = $detailsCaches;

        return $this;
    }

    public function isAutoAccept(): bool
    {
        return $this->autoAccept;
    }

    public function setAutoAccept(bool $autoAccept): self
    {
        $this->autoAccept = $autoAccept;

        return $this;
    }

    public function isDraft(): bool
    {
        return $this->isDraft;
    }

    public function setIsDraft(bool $isDraft): self
    {
        $this->isDraft = $isDraft;

        return $this;
    }
}
