<?php

namespace App\EventListener;

use App\Entity\User;
use App\Service\MailerLiteService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Psr\Log\LoggerInterface;

#[AsEntityListener(event: Events::postPersist, entity: User::class)]
class NewMemberMailerLiteListener
{
    private bool $enabled;
    
    public function __construct(
        private readonly MailerLiteService $mailerLiteService,
        private readonly LoggerInterface $logger,
        string $mailerLiteEnabled = 'false'
    ) {
        $this->enabled = $mailerLiteEnabled === 'true';
    }
    
    public function postPersist(User $user, LifecycleEventArgs $event): void
    {
        // Ne pas synchroniser si désactivé
        if (!$this->enabled) {
            return;
        }
        
        // Ne synchroniser que les nouveaux membres (pas les users manuels ou nomades)
        if ($user->isManuel() || $user->isNomade()) {
            return;
        }
        
        // Vérifier que c'est bien un nouveau membre (tsInsert récent)
        $tsInsert = $user->getTsInsert();
        if (!$tsInsert || (time() - $tsInsert) > 3600) { // Plus d'une heure
            return;
        }
        
        // Ne synchroniser que si l'utilisateur a un email
        if (!$user->getEmail()) {
            $this->logger->info('New member ' . $user->getId() . ' has no email, skipping MailerLite sync');
            return;
        }
        
        try {
            $this->logger->info('Syncing new member ' . $user->getId() . ' to MailerLite');
            $this->mailerLiteService->addNewMember($user);
        } catch (\Exception $e) {
            // Ne pas faire échouer la transaction principale si MailerLite échoue
            $this->logger->error('Failed to sync new member to MailerLite: ' . $e->getMessage());
        }
    }
}