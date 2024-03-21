<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240307092240 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX id_user ON caf_user (id_user)');
        $this->addSql('ALTER TABLE sessions CHANGE sess_data sess_data LONGBLOB NOT NULL');

        if ($_ENV['APP_ENV'] != 'dev') {
            $this->addSql('ALTER TABLE sessions RENAME INDEX expiry TO sess_lifetime_idx');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX id_user ON caf_user');
        $this->addSql('ALTER TABLE sessions CHANGE sess_data sess_data BLOB NOT NULL');
        
        if ($_ENV['APP_ENV'] != 'dev') {
            $this->addSql('ALTER TABLE sessions RENAME INDEX sess_lifetime_idx TO EXPIRY');
        }

    }
}
