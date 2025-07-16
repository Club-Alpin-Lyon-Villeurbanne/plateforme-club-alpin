<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250710130916 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            INSERT INTO `caf_userright` (`code_userright`, `title_userright`, `ordre_userright`, `parent_userright`) VALUES ('evt_cancel_own', 'Annuler une sortie (désactivation) qu''on organise ou qu''on encadre', '152', 'GESTION DES SORTIES ');
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM caf_userright WHERE `code_userright` = \'evt_cancel_own\'');
    }
}
