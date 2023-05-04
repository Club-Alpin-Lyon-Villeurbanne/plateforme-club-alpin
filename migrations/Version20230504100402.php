<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230504100402 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('IF  EXISTS(SELECT NULL FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = \'messenger_messages\' AND table_schema = \''.$this->connection->getDatabase().'\') THEN ALTER TABLE messenger_messages MODIFY body longtext COLLATE utf8mb4_unicode_ci NOT NULL; END IF');
        $this->addSql('IF  EXISTS(SELECT NULL FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = \'messenger_messages\' AND table_schema = \''.$this->connection->getDatabase().'\') THEN ALTER TABLE messenger_messages MODIFY headers longtext COLLATE utf8mb4_unicode_ci NOT NULL; END IF');
        $this->addSql('IF  EXISTS(SELECT NULL FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = \'messenger_messages\' AND table_schema = \''.$this->connection->getDatabase().'\') THEN ALTER TABLE messenger_messages MODIFY queue_name varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL; END IF');
    }

    public function down(Schema $schema): void
    {
    }
}
