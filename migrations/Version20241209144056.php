<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241209144056 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE caf_user CHANGE alert_sortie_prefix alert_sortie_prefix VARCHAR(255) NULL, CHANGE alert_article_prefix alert_article_prefix VARCHAR(255) NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user CHANGE alert_sortie_prefix alert_sortie_prefix VARCHAR(255) DEFAULT \'[CAF-Lyon-Sortie]\' NOT NULL, CHANGE alert_article_prefix alert_article_prefix VARCHAR(255) DEFAULT \'[CAF-Lyon-Article]\' NOT NULL');
    }
}
