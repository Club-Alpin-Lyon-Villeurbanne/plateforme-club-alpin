<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260127132046 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Removes no longer used page';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM caf_page WHERE code_page = \'admin-matrice-droits\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<SQL
            INSERT INTO `caf_page` (`id_page`, `parent_page`, `admin_page`, `superadmin_page`, `vis_page`, `ordre_page`, `menu_page`, `ordre_menu_page`, `menuadmin_page`, `ordre_menuadmin_page`, `code_page`, `default_name_page`, `meta_title_page`, `meta_description_page`, `priority_page`, `add_css_page`, `add_js_page`, `lock_page`, `pagelibre_page`, `created_page`)
            VALUES (5, 0, 1, 0, 1, 0, 0, 0, 1, 1, 'admin-matrice-droits', 'Matrice des droits', 1, 0, 0.0, '', '', 0, 0, 0);
        SQL);
    }
}
