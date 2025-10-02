<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250911153121 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds new editable text';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            INSERT INTO `caf_content_html` (`code_content_html`, `lang_content_html`, `contenu_content_html`, `date_content_html`, `linkedtopage_content_html`, `current_content_html`, `vis_content_html`)
            VALUES ('commission_config_fields', 'fr', '', UNIX_TIMESTAMP(), '', 1, 1);
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM caf_content_html WHERE code_content_html = \'commission_config_fields\'');
    }
}
