<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260121101522 extends AbstractMigration
{
    /**
     * Provides a human-readable description of this migration.
     *
     * @return string The migration description: "Adds missing page for legacy".
     */
    public function getDescription(): string
    {
        return 'Adds missing page for legacy';
    }

    /**
     * Adds a legacy page record with code 'commission-consulter' to the `caf_page` table.
     *
     * @param Schema $schema The schema instance for this migration (unused directly; provided by migration runner).
     */
    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            INSERT INTO `caf_page` (`parent_page`, `admin_page`, `superadmin_page`, `vis_page`, `ordre_page`, `menu_page`, `ordre_menu_page`, `menuadmin_page`, `ordre_menuadmin_page`, `code_page`, `default_name_page`, `meta_title_page`, `meta_description_page`, `priority_page`, `add_css_page`, `add_js_page`, `lock_page`, `pagelibre_page`, `created_page`)
            VALUES (0, 0, 0, 1, 0, 0, 0, 0, 0, 'commission-consulter', 'Fiche commission', 0, 0, '0.0', '', '', 0, 0, 0);
        SQL);
    }

    /**
     * Removes the legacy page with code 'commission-consulter' from the caf_page table.
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM caf_page WHERE code_page = \'commission-consulter\'');
    }
}