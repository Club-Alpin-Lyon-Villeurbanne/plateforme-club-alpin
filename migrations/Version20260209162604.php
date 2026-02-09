<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute des index sur expense_report pour optimiser les requêtes de liste
 *
 * Performance improvement pour /admin/notes-de-frais :
 * - Index sur status : filtre WHERE status != 'draft'
 * - Index sur created_at : tri ORDER BY created_at DESC
 * - Index composé (status, created_at) : requête complète optimisée
 *
 * Gain attendu : 30-70% de performance
 */
final class Version20260209162604 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add performance indexes on expense_report (status, created_at)';
    }

    public function up(Schema $schema): void
    {
        // Index simple sur status (pour WHERE status != 'draft' ou WHERE status IN (...))
        $this->addSql('CREATE INDEX IDX_expense_report_status ON expense_report (status)');

        // Index simple sur created_at (pour ORDER BY created_at DESC)
        $this->addSql('CREATE INDEX IDX_expense_report_created_at ON expense_report (created_at)');

        // Index composé optimal pour la requête complète
        // MySQL utilisera cet index pour : WHERE status IN (...) ORDER BY created_at DESC
        $this->addSql('CREATE INDEX IDX_expense_report_status_created ON expense_report (status, created_at DESC)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_expense_report_status ON expense_report');
        $this->addSql('DROP INDEX IDX_expense_report_created_at ON expense_report');
        $this->addSql('DROP INDEX IDX_expense_report_status_created ON expense_report');
    }
}
