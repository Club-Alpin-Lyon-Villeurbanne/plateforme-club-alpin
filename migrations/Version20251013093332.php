<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251013093332 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            INSERT INTO `caf_content_html` (`code_content_html`, `lang_content_html`, `contenu_content_html`, `date_content_html`, `linkedtopage_content_html`, `current_content_html`, `vis_content_html`)
            VALUES ('signalement-intro', 'fr', '<p>Pour écrire à l''équipe de la cellule signalement sans utiliser votre adresse personnelle et/ou en restant anonyme, vous pouvez compléter le questionnaire ci-dessous. Les situations rapportées anonymement seront entendues, mais l''équipe ne pourra y apporter un suivi.</p>', UNIX_TIMESTAMP(), '', 1, 1);
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM caf_content_html WHERE code_content_html = \'signalement-intro\'');
    }
}
