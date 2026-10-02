<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260606101500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Sorties : ajoute l'indicateur « sortie à l'étranger » (commune de départ facultative)";
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE caf_evt ADD is_etranger TINYINT(1) DEFAULT 0 NOT NULL COMMENT 'Sortie à l''étranger : commune de départ non requise'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt DROP is_etranger');
    }
}
