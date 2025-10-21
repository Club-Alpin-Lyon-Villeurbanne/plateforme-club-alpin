<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251015074425 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds new table to store infos about unrecognized payers by event (Hello Asso)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE event_unrecognized_payer (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, payer_email VARCHAR(255) NOT NULL, has_paid TINYINT(1) DEFAULT 0 NOT NULL, INDEX IDX_D7DC845571F7E88B (event_id), INDEX id (id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE event_unrecognized_payer ADD CONSTRAINT FK_D7DC845571F7E88B FOREIGN KEY (event_id) REFERENCES caf_evt (id_evt) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_unrecognized_payer ADD payer_lastname VARCHAR(255) NOT NULL, ADD payer_firstname VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event_unrecognized_payer DROP FOREIGN KEY FK_D7DC845571F7E88B');
        $this->addSql('DROP TABLE event_unrecognized_payer');
    }
}
