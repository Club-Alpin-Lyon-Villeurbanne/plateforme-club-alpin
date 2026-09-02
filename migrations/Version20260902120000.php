<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260902120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adhérents : élargit ville_user à 50 caractères (la FFCAM en annonce 33, la colonne en acceptait 30)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user CHANGE ville_user ville_user VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE caf_user SET ville_user = LEFT(ville_user, 30)');
        $this->addSql('ALTER TABLE caf_user CHANGE ville_user ville_user VARCHAR(30) DEFAULT NULL');
    }
}
