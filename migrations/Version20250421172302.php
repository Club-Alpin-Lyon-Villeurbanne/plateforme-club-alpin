<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250421172302 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE expense_report_status_history (id INT AUTO_INCREMENT NOT NULL, expense_report_id INT NOT NULL, changed_by_id BIGINT NOT NULL, old_status VARCHAR(255) DEFAULT NULL, new_status VARCHAR(255) NOT NULL, changed_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_BA8989598F758FBA (expense_report_id), INDEX IDX_BA898959828AD0A0 (changed_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE expense_report_status_history ADD CONSTRAINT FK_BA8989598F758FBA FOREIGN KEY (expense_report_id) REFERENCES expense_report (id)');
        $this->addSql('ALTER TABLE expense_report_status_history ADD CONSTRAINT FK_BA898959828AD0A0 FOREIGN KEY (changed_by_id) REFERENCES caf_user (id_user)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE expense_report_status_history DROP FOREIGN KEY FK_BA8989598F758FBA');
        $this->addSql('ALTER TABLE expense_report_status_history DROP FOREIGN KEY FK_BA898959828AD0A0');
        $this->addSql('DROP TABLE expense_report_status_history');
    }
}
