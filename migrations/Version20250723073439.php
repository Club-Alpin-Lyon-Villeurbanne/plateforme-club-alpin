<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250723073439 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE caf_competence (id INT AUTO_INCREMENT NOT NULL, code_activite VARCHAR(10) NOT NULL, activite VARCHAR(100) NOT NULL, niveau VARCHAR(255) NOT NULL, theme VARCHAR(255) NOT NULL, code_competence VARCHAR(15) NOT NULL, titre VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE caf_competence');
    }
}
