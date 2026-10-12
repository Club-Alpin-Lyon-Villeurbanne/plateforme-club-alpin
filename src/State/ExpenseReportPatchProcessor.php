<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\ExpenseReport;
use App\Service\ExpenseReportCalculator;
use App\Service\NoteDeFrais\NotificationsNoteHorsSortie;
use App\Utils\Enums\ExpenseReportStatusEnum;
use App\Validator\ExpenseReport\ExpenseReportOriginalState;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Modification d'une note de frais. L'envoi d'une note hors sortie (brouillon ou note à
 * corriger qui passe à « envoyée ») fige ses montants (et le taux kilométrique) et efface le
 * message du renvoi dans la même écriture que le changement de statut. Chaque changement de
 * statut d'une note hors sortie est ensuite annoncé par mail.
 *
 * `version`, facultatif : la version de la note lue par le client. Le formulaire du site l'envoie
 * pour qu'un onglet resté ouvert n'écrase pas ce qui a été enregistré ailleurs ; compta-club ne
 * l'envoie pas.
 */
class ExpenseReportPatchProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persistProcessor,
        private readonly ExpenseReportOriginalState $originalState,
        private readonly ExpenseReportCalculator $calculator,
        private readonly NotificationsNoteHorsSortie $notifications,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $versionLue = $this->versionLue($context['request'] ?? null);
        if ($data instanceof ExpenseReport && null !== $versionLue && $versionLue !== $data->getVersion()) {
            throw new ConflictHttpException('Cette note a été modifiée dans un autre onglet ou sur un autre appareil.');
        }

        $changementDeStatut = $data instanceof ExpenseReport
            && $data->getType()->estHorsSortie()
            && $this->originalState->status($data) !== $data->getStatus();

        if ($changementDeStatut && ExpenseReportStatusEnum::SUBMITTED === $data->getStatus()) {
            $this->calculator->figerNoteHorsSortie($data);
            // Le message du renvoi est traité ; il reste dans l'historique. Un nouveau renvoi devra en écrire un.
            $data->setStatusComment(null);
        }

        try {
            $resultat = $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        } catch (OptimisticLockException $conflit) {
            // Rien n'est écrit : la note a changé depuis que cette requête l'a lue.
            throw new ConflictHttpException('Cette note a été modifiée en même temps par une autre action.', $conflit);
        }

        if ($changementDeStatut) {
            $this->notifications->statutChange($data);
        }

        return $resultat;
    }

    private function versionLue(?Request $requete): ?int
    {
        $corps = null !== $requete ? json_decode($requete->getContent(), true) : null;
        $version = \is_array($corps) ? ($corps['version'] ?? null) : null;
        if (null !== $version && !\is_int($version)) {
            throw new BadRequestHttpException('La version de la note doit être un nombre entier.');
        }

        return $version;
    }
}
