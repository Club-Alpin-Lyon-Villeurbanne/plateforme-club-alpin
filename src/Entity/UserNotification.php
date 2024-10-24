<?php

namespace App\Entity;

use App\Repository\UserNotificationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'caf_user_notification')]
#[ORM\Index(name: 'user_notif_signature', columns: ['signature'])]
#[ORM\Entity(repositoryClass: UserNotificationRepository::class)]
class UserNotification
{
    #[ORM\Column(type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: 'User', fetch: 'EAGER')]
    #[ORM\JoinColumn(referencedColumnName: 'id_user', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'string', length: 10, nullable: false)]
    private string $type;

    #[ORM\Column(type: 'string', length: 64, nullable: false)]
    private string $entityId;

    #[ORM\Column(type: 'string', length: 200, nullable: false, unique: true)]
    private string $signature;

    public function __construct(User $user, AlertType $type, string|int $entityId)
    {
        $this->user = $user;
        $this->entityId = (string) $entityId;
        $this->type = $type->name;
        $this->signature = self::generateSignature($user, $type, $entityId);
    }

    public static function generateSignature(User $user, AlertType $type, string|int $entityId): string
    {
        return sprintf('%s-%s-%s', $user->getId(), $type->name, $entityId);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getEntityId(): string
    {
        return $this->entityId;
    }

    public function setEntityId(string $entityId): void
    {
        $this->entityId = $entityId;
    }

    public function getSignature(): string
    {
        return $this->signature;
    }

    public function setSignature(string $signature): void
    {
        $this->signature = $signature;
    }
}
