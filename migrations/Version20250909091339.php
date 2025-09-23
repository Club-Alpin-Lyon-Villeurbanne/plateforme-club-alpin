<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250909091339 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds new line in rights matrix';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            INSERT INTO `caf_userright` (`code_userright`, `title_userright`, `ordre_userright`, `parent_userright`) VALUES ('evt_nomad_add', 'Ajouter des nomades', '235', 'GESTION DES SORTIES ');
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM caf_userright WHERE `code_userright` = \'evt_nomad_add\'');
    }
}
