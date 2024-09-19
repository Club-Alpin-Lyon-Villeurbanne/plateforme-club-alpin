<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240903172741 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE expense_attachment (id INT AUTO_INCREMENT NOT NULL, expense_report_id INT NOT NULL, user_id BIGINT NOT NULL, expense_id VARCHAR(255) NOT NULL, file_name VARCHAR(255) NOT NULL, file_path VARCHAR(255) NOT NULL, INDEX IDX_C94BE6B28F758FBA (expense_report_id), INDEX IDX_C94BE6B2A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE expense_attachment ADD CONSTRAINT FK_C94BE6B28F758FBA FOREIGN KEY (expense_report_id) REFERENCES expense_report (id)');
        $this->addSql('ALTER TABLE expense_attachment ADD CONSTRAINT FK_C94BE6B2A76ED395 FOREIGN KEY (user_id) REFERENCES caf_user (id_user)');
        $this->addSql('ALTER TABLE expense DROP FOREIGN KEY FK_2D3A8DA68F758FBA');
        $this->addSql('ALTER TABLE expense DROP FOREIGN KEY FK_2D3A8DA6A857C7A9');
        $this->addSql('ALTER TABLE expense_field DROP FOREIGN KEY FK_F8FDE26266BD6C4D');
        $this->addSql('ALTER TABLE expense_field DROP FOREIGN KEY FK_F8FDE262F395DB7B');
        $this->addSql('ALTER TABLE expense_type DROP FOREIGN KEY FK_3879194B38351BBE');
        $this->addSql('ALTER TABLE expense_type_expense_field_type DROP FOREIGN KEY FK_3996E5A7A857C7A9');
        $this->addSql('ALTER TABLE expense_type_expense_field_type DROP FOREIGN KEY FK_3996E5A766BD6C4D');
        $this->addSql('DROP TABLE expense');
        $this->addSql('DROP TABLE expense_field');
        $this->addSql('DROP TABLE expense_field_type');
        $this->addSql('DROP TABLE expense_group');
        $this->addSql('DROP TABLE expense_type');
        $this->addSql('DROP TABLE expense_type_expense_field_type');
        $this->addSql('ALTER TABLE expense_report ADD details JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE expense (id INT AUTO_INCREMENT NOT NULL, expense_report_id INT NOT NULL, expense_type_id INT NOT NULL, spent_amount INT NOT NULL, refund_amount INT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_2D3A8DA68F758FBA (expense_report_id), INDEX IDX_2D3A8DA6A857C7A9 (expense_type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE expense_field (id INT AUTO_INCREMENT NOT NULL, expense_field_type_id INT NOT NULL, expense_id INT NOT NULL, justification_document VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, value VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_F8FDE262F395DB7B (expense_id), INDEX IDX_F8FDE26266BD6C4D (expense_field_type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE expense_field_type (id INT AUTO_INCREMENT NOT NULL, slug VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, input_type VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE expense_group (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, slug VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, type VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE expense_type (id INT AUTO_INCREMENT NOT NULL, expense_group_id INT DEFAULT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, slug VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_3879194B38351BBE (expense_group_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE expense_type_expense_field_type (id INT AUTO_INCREMENT NOT NULL, expense_field_type_id INT NOT NULL, expense_type_id INT NOT NULL, needs_justification TINYINT(1) NOT NULL, is_used_for_total TINYINT(1) NOT NULL, is_mandatory TINYINT(1) NOT NULL, display_order INT NOT NULL, INDEX IDX_3996E5A7A857C7A9 (expense_type_id), INDEX IDX_3996E5A766BD6C4D (expense_field_type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE expense ADD CONSTRAINT FK_2D3A8DA68F758FBA FOREIGN KEY (expense_report_id) REFERENCES expense_report (id)');
        $this->addSql('ALTER TABLE expense ADD CONSTRAINT FK_2D3A8DA6A857C7A9 FOREIGN KEY (expense_type_id) REFERENCES expense_type (id)');
        $this->addSql('ALTER TABLE expense_field ADD CONSTRAINT FK_F8FDE26266BD6C4D FOREIGN KEY (expense_field_type_id) REFERENCES expense_field_type (id)');
        $this->addSql('ALTER TABLE expense_field ADD CONSTRAINT FK_F8FDE262F395DB7B FOREIGN KEY (expense_id) REFERENCES expense (id)');
        $this->addSql('ALTER TABLE expense_type ADD CONSTRAINT FK_3879194B38351BBE FOREIGN KEY (expense_group_id) REFERENCES expense_group (id)');
        $this->addSql('ALTER TABLE expense_type_expense_field_type ADD CONSTRAINT FK_3996E5A7A857C7A9 FOREIGN KEY (expense_type_id) REFERENCES expense_type (id)');
        $this->addSql('ALTER TABLE expense_type_expense_field_type ADD CONSTRAINT FK_3996E5A766BD6C4D FOREIGN KEY (expense_field_type_id) REFERENCES expense_field_type (id)');
        $this->addSql('ALTER TABLE expense_attachment DROP FOREIGN KEY FK_C94BE6B28F758FBA');
        $this->addSql('ALTER TABLE expense_attachment DROP FOREIGN KEY FK_C94BE6B2A76ED395');
        $this->addSql('DROP TABLE expense_attachment');
        $this->addSql('DROP INDEX UNIQ_DEBE82686A22D67B ON caf_user');
        $this->addSql('ALTER TABLE expense_report DROP details');
        $this->addSql('ALTER TABLE sessions CHANGE sess_data sess_data BLOB NOT NULL');
        $this->addSql('ALTER TABLE sessions RENAME INDEX sess_lifetime_idx TO EXPIRY');
    }
}
