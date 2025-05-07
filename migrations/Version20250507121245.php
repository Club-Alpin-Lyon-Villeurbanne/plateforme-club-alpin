<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20250507121245 extends AbstractMigration
{


    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user ADD materiel_account_created_at DATETIME DEFAULT NULL COMMENT \'Date de création du compte sur la plateforme de matériel\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user DROP materiel_account_created_at');
    }
}
