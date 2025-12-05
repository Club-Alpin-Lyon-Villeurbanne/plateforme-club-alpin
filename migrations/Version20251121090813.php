<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121090813 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Changes right description';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE caf_userright SET title_userright = \'Visualiser la liste des commissions pour lesquelles on a des responsabilités\' WHERE `code_userright` = \'commission_list\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE caf_userright SET title_userright = \'Visualiser la liste des commissions dont on est responsable\' WHERE `code_userright` = \'commission_list\'');
    }
}
