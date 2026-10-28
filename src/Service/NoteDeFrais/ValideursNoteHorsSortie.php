<?php

namespace App\Service\NoteDeFrais;

use App\Entity\Commission;
use App\Entity\ExpenseReport;
use App\Entity\User;
use App\Entity\UserAttr;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Les personnes qui valident une note hors sortie avant la trésorerie : les responsables de sa
 * commission (rôle `responsable-commission`, adhérents ni supprimés, ni verrouillés, ni à
 * renouveler, avec une adresse mail), demandeur excepté. Les droits, les destinataires des mails
 * et les pages en dépendent : la règle n'est écrite qu'ici, et recalculée à chaque lecture.
 */
class ValideursNoteHorsSortie implements ResetInterface
{
    /** @var array<int, list<User>> responsables par commission, le temps d'une requête */
    private array $responsables = [];

    /** @var array<int, list<int>> commissions de chaque responsable, le temps d'une requête */
    private array $commissionsValidees = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * Une liste vide signifie que la note va directement à la trésorerie.
     *
     * @return list<User>
     */
    public function pour(ExpenseReport $note): array
    {
        $commission = $note->getCommission();
        if (!$note->getType()->passeParUnResponsable() || null === $commission) {
            return [];
        }

        return $this->sauf($note->getUser(), $this->responsablesDe([$commission])[$commission->getId()]);
    }

    public function estValideur(User $utilisateur, ExpenseReport $note): bool
    {
        foreach ($this->pour($note) as $valideur) {
            if ($valideur->getId() === $utilisateur->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Valideurs de la note que ce demandeur déposerait sur chacune de ces commissions.
     *
     * @param list<Commission> $commissions
     *
     * @return array<int, list<User>> par identifiant de commission
     */
    public function pourLesCommissions(array $commissions, User $demandeur): array
    {
        $responsables = $this->responsablesDe($commissions);

        $valideurs = [];
        foreach ($commissions as $commission) {
            $valideurs[$commission->getId()] = $this->sauf($demandeur, $responsables[$commission->getId()]);
        }

        return $valideurs;
    }

    /**
     * Les commissions dont cet utilisateur est un responsable actif : il en valide les notes des
     * autres demandeurs.
     *
     * @return list<int>
     */
    public function commissionsValideesPar(User $utilisateur): array
    {
        if (!isset($this->commissionsValidees[$utilisateur->getId()])) {
            $ids = $this->responsablesActifs()
                ->select('commission.id')
                ->andWhere('role.user = :utilisateur')
                ->setParameter('utilisateur', $utilisateur)
                ->getQuery()
                ->getSingleColumnResult();
            $this->commissionsValidees[$utilisateur->getId()] = array_values(array_unique(array_map('intval', $ids)));
        }

        return $this->commissionsValidees[$utilisateur->getId()];
    }

    /**
     * Forme donnée à l'API et aux pages.
     *
     * @param list<User> $valideurs
     *
     * @return list<array{id: ?int, prenom: ?string, nom: ?string}>
     */
    public static function exposer(array $valideurs): array
    {
        return array_map(static fn (User $valideur) => ['id' => $valideur->getId(), 'prenom' => $valideur->getFirstname(), 'nom' => $valideur->getLastname()], $valideurs);
    }

    public function reset(): void
    {
        $this->responsables = [];
        $this->commissionsValidees = [];
    }

    /**
     * @param list<Commission> $commissions
     *
     * @return array<int, list<User>> par identifiant de commission, triés par prénom puis nom
     */
    private function responsablesDe(array $commissions): array
    {
        $ids = array_values(array_diff(array_unique(array_map(static fn (Commission $commission) => (int) $commission->getId(), $commissions)), array_keys($this->responsables)));

        if ($ids) {
            $lignes = $this->responsablesActifs()
                ->select('role', 'responsable', 'commission.id AS commission_id')
                ->andWhere('commission.id IN (:commissions)')
                ->setParameter('commissions', $ids)
                ->orderBy('responsable.firstname', 'ASC')
                ->addOrderBy('responsable.lastname', 'ASC')
                ->getQuery()
                ->getResult();

            $parCommission = array_fill_keys($ids, []);
            foreach ($lignes as $ligne) {
                $responsable = $ligne[0]->getUser();
                // Un même adhérent peut avoir reçu deux fois le rôle.
                $parCommission[(int) $ligne['commission_id']][$responsable->getId()] = $responsable;
            }
            foreach ($parCommission as $id => $responsables) {
                $this->responsables[$id] = array_values($responsables);
            }
        }

        return $this->responsables;
    }

    /**
     * Condition DQL sur les notes de `$notes` (alias `$alias`) : elles ont au moins un valideur, ou
     * aucun (elles vont alors à la trésorerie). Les paramètres sont posés sur `$notes`.
     */
    public function conditionValideurs(QueryBuilder $notes, string $alias, bool $avecValideurs): string
    {
        $valideurs = $this->responsablesActifs('_valideur')
            ->select('role_valideur.id')
            ->andWhere(sprintf('commission_valideur.id = IDENTITY(%s.commission)', $alias))
            ->andWhere(sprintf('responsable_valideur.id != IDENTITY(%s.user)', $alias));
        foreach ($valideurs->getParameters() as $parametre) {
            $notes->setParameter($parametre->getName(), $parametre->getValue());
        }

        return sprintf('%sEXISTS (%s)', $avecValideurs ? '' : 'NOT ', $valideurs->getDQL());
    }

    /**
     * Le rôle `responsable-commission` sur une commission (role, commission), porté par un adhérent
     * ni supprimé, ni verrouillé, ni à renouveler, qui a une adresse mail (responsable) : un compte
     * verrouillé ne se connecte plus, et sans adresse il ne saurait pas qu'une note l'attend. Le
     * suffixe distingue les alias dans une sous-requête.
     */
    private function responsablesActifs(string $suffixe = ''): QueryBuilder
    {
        [$role, $responsable, $type, $commission] = array_map(static fn (string $alias) => $alias . $suffixe, ['role', 'responsable', 'type_de_role', 'commission']);

        return $this->em->createQueryBuilder()
            ->from(UserAttr::class, $role)
            ->join(sprintf('%s.user', $role), $responsable)
            ->join(sprintf('%s.userType', $role), $type)
            ->from(Commission::class, $commission)
            ->andWhere(sprintf("%s.params = CONCAT('commission:', %s.code)", $role, $commission))
            ->andWhere(sprintf('%s.code = :responsable_commission%s', $type, $suffixe))
            ->andWhere(sprintf('%s.doitRenouveler = false', $responsable))
            ->andWhere(sprintf('%s.isDeleted = false', $responsable))
            ->andWhere(sprintf('%s.isLocked = false', $responsable))
            ->andWhere(sprintf('%1$s.email IS NOT NULL AND %1$s.email != \'\'', $responsable))
            ->setParameter('responsable_commission' . $suffixe, UserAttr::RESPONSABLE_COMMISSION);
    }

    /**
     * @param list<User> $responsables
     *
     * @return list<User>
     */
    private function sauf(?User $demandeur, array $responsables): array
    {
        return array_values(array_filter($responsables, static fn (User $responsable) => $responsable->getId() !== $demandeur?->getId()));
    }
}
