<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221018180318 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt CHANGE tsp_evt tsp_evt BIGINT DEFAULT NULL COMMENT \'timestamp du début du event\', CHANGE tsp_end_evt tsp_end_evt BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE caf_evt CHANGE join_start_evt join_start_evt INT DEFAULT NULL COMMENT \'Timestamp de départ des inscriptions\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt CHANGE join_start_evt join_start_evt INT NOT NULL COMMENT \'Timestamp de départ des inscriptions\'');
        $this->addSql('ALTER TABLE caf_evt CHANGE tsp_evt tsp_evt BIGINT NOT NULL COMMENT \'timestamp du début du event\', CHANGE tsp_end_evt tsp_end_evt BIGINT NOT NULL');
    }
}
