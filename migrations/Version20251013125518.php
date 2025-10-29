<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251013125518 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            INSERT INTO `caf_content_html` (`code_content_html`, `lang_content_html`, `contenu_content_html`, `date_content_html`, `linkedtopage_content_html`, `current_content_html`, `vis_content_html`)
            VALUES ('bloc-partenaires', 'fr', '<h1 class="partenaires-h1">nos partenaires</h1><a href="/pages/nos-partenaires-publics.html" class="public">🏛️ Soutiens institutionnels</a><a href="/pages/nos-partenaires-prives.html" class="private">🎁 Avantages adhérents</a>', UNIX_TIMESTAMP(), '', 1, 1);
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM caf_content_html WHERE code_content_html = \'bloc-partenaires\'');
    }
}
