<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250828140627 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            INSERT INTO `caf_userright` (`code_userright`, `title_userright`, `ordre_userright`, `parent_userright`) VALUES ('user_see_status_list', 'Visualiser la liste des responsabilités des adhérents', '375', 'GESTION DES COMPTES ADHERENTS');
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM caf_userright WHERE `code_userright` = \'user_see_status_list\'');
    }
}
