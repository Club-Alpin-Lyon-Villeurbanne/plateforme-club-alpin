<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250617085540 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            UPDATE caf_content_html SET contenu_content_html = '<h2><span class="bleucaf">&gt;</span> Retrouvez ci-dessous la liste des sorties que vous proposez, de la plus lointaine dans le futur à la plus lointaine dans le passé.</h2>' WHERE code_content_html = 'profil-sorties-self';
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<SQL
            UPDATE caf_content_html SET contenu_content_html = '<h2><span class="bleucaf">&gt;</span> Retrouvez ci-dessous la liste des sorties que vous proposez, par ordre de création de la plus récente à la plus ancienne.</h2>' WHERE code_content_html = 'profil-sorties-self';
        SQL);
    }
}
