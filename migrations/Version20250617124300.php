<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250617124300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            UPDATE `caf_userright` SET `title_userright` = 'Associer un encadrant / stagiaire' WHERE `code_userright` LIKE 'comm_lier_encadrant';
            UPDATE `caf_userright` SET `title_userright` = 'Désassocier un encadrant / stagiaire' WHERE `code_userright` LIKE 'comm_delier_encadrant';
            UPDATE `caf_userright` SET `title_userright` = 'Donner des droits d''encadrement (+coenc +bénév +rédac)' WHERE `code_userright` LIKE 'user_giveright_1';
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<SQL
            UPDATE `caf_userright` SET `title_userright` = 'Associer un encadrant / co-encadrant' WHERE `code_userright` LIKE 'comm_lier_encadrant';
            UPDATE `caf_userright` SET `title_userright` = 'Désassocier un encadrant / co-encadrant' WHERE `code_userright` LIKE 'comm_delier_encadrant';
            UPDATE `caf_userright` SET `title_userright` = 'Donner des droits d''encadrement (+coenc +bénév)' WHERE `code_userright` LIKE 'user_giveright_1';
        SQL);
    }
}
