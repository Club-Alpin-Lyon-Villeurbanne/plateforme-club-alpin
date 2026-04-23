<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260423103744 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create processed_hello_asso_payment table for payment webhook idempotency';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE processed_hello_asso_payment (
            id INT AUTO_INCREMENT NOT NULL,
            hello_asso_payment_id VARCHAR(64) NOT NULL,
            reservation_id INT NOT NULL,
            processed_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_hello_asso_payment_id (hello_asso_payment_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE processed_hello_asso_payment');
    }
}
