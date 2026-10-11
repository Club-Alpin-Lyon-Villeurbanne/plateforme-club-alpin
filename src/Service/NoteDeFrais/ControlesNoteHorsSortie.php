<?php

namespace App\Service\NoteDeFrais;

use App\Entity\ExpenseReport;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Contrôles de la politique de remboursement sur les notes hors sortie.
 *
 * Ajouter un contrôle : écrire une fonction qui renvoie un message lisible par le demandeur et
 * le valideur (null si rien à signaler), puis l'inscrire dans controles() avec ses moments et
 * son niveau. Les seuils se lisent dans la configuration `expense_report`.
 */
final class ControlesNoteHorsSortie
{
    public function __construct(
        #[Autowire(param: 'expense_report')]
        private readonly array $config,
    ) {
    }

    /**
     * @return list<array{champ: string, message: string}>
     */
    public function bloquants(ExpenseReport $note, MomentDeControle $moment): array
    {
        return $this->constats($note, $moment, NiveauDeControle::BLOQUANT);
    }

    /**
     * @return list<array{champ: string, moments: list<MomentDeControle>, niveau: NiveauDeControle, controle: callable(ExpenseReport): ?string}>
     */
    private function controles(): array
    {
        return [
            [
                'champ' => 'dateDepense',
                'moments' => [MomentDeControle::CREATION, MomentDeControle::CHAQUE_SOUMISSION],
                'niveau' => NiveauDeControle::BLOQUANT,
                'controle' => $this->dateDansLeFutur(...),
            ],
            [
                'champ' => 'dateDepense',
                'moments' => [MomentDeControle::CREATION, MomentDeControle::PREMIERE_SOUMISSION],
                'niveau' => NiveauDeControle::BLOQUANT,
                'controle' => $this->delaiDEnvoiDepasse(...),
            ],
        ];
    }

    private function dateDansLeFutur(ExpenseReport $note): ?string
    {
        $date = $note->getDateDepense();

        return null !== $date && $date > new \DateTimeImmutable('today')
            ? 'La date ne peut pas être dans le futur.'
            : null;
    }

    private function delaiDEnvoiDepasse(ExpenseReport $note): ?string
    {
        $date = $note->getDateDepense();
        $delai = $this->config['horsSortie']['delaiEnvoiJours'][$note->getType()->value] ?? null;

        if (null === $date || null === $delai) {
            return null;
        }

        return $date->modify(sprintf('+%d days', $delai)) < new \DateTimeImmutable('today')
            ? sprintf('Le délai de %d jours après la dépense est dépassé.', $delai)
            : null;
    }

    /**
     * @return list<array{champ: string, message: string}>
     */
    private function constats(ExpenseReport $note, MomentDeControle $moment, NiveauDeControle $niveau): array
    {
        if (!$note->getType()->estHorsSortie()) {
            return [];
        }

        $constats = [];
        foreach ($this->controles() as $controle) {
            if ($controle['niveau'] !== $niveau || !\in_array($moment, $controle['moments'], true)) {
                continue;
            }

            $message = $controle['controle']($note);
            if (null !== $message) {
                $constats[] = ['champ' => $controle['champ'], 'message' => $message];
            }
        }

        return $constats;
    }
}
