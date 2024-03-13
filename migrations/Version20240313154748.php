<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240313154748 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE expense CHANGE spent_amount spent_amount INT NOT NULL, CHANGE refund_amount refund_amount INT DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE expense ADD CONSTRAINT FK_2D3A8DA6A857C7A9 FOREIGN KEY (expense_type_id) REFERENCES expense_type (id)');
        $this->addSql('ALTER TABLE expense ADD CONSTRAINT FK_2D3A8DA68F758FBA FOREIGN KEY (expense_report_id) REFERENCES expense_report (id)');
        $this->addSql('CREATE INDEX IDX_2D3A8DA6A857C7A9 ON expense (expense_type_id)');
        $this->addSql('CREATE INDEX IDX_2D3A8DA68F758FBA ON expense (expense_report_id)');
        $this->addSql('ALTER TABLE expense_field CHANGE value value VARCHAR(255) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE expense_field ADD CONSTRAINT FK_F8FDE262F395DB7B FOREIGN KEY (expense_id) REFERENCES expense (id)');
        $this->addSql('ALTER TABLE expense_field ADD CONSTRAINT FK_F8FDE26266BD6C4D FOREIGN KEY (expense_field_type_id) REFERENCES expense_field_type (id)');
        $this->addSql('CREATE INDEX IDX_F8FDE262F395DB7B ON expense_field (expense_id)');
        $this->addSql('CREATE INDEX IDX_F8FDE26266BD6C4D ON expense_field (expense_field_type_id)');
        $this->addSql('ALTER TABLE expense_field_type CHANGE input_type input_type VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE expense_group CHANGE type type VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE expense_report CHANGE refund_required refund_required TINYINT(1) NOT NULL, CHANGE user_id user_id BIGINT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE expense_report ADD CONSTRAINT FK_280A691A76ED395 FOREIGN KEY (user_id) REFERENCES caf_user (id_user)');
        $this->addSql('ALTER TABLE expense_report ADD CONSTRAINT FK_280A69171F7E88B FOREIGN KEY (event_id) REFERENCES caf_evt (id_evt)');
        $this->addSql('CREATE INDEX IDX_280A691A76ED395 ON expense_report (user_id)');
        $this->addSql('CREATE INDEX IDX_280A69171F7E88B ON expense_report (event_id)');
        $this->addSql('ALTER TABLE expense_type CHANGE expense_group_id expense_group_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE expense_type ADD CONSTRAINT FK_3879194B38351BBE FOREIGN KEY (expense_group_id) REFERENCES expense_group (id)');
        $this->addSql('CREATE INDEX IDX_3879194B38351BBE ON expense_type (expense_group_id)');
        $this->addSql('ALTER TABLE expense_type_expense_field_type CHANGE needs_justification needs_justification TINYINT(1) NOT NULL, CHANGE is_used_for_total is_used_for_total TINYINT(1) NOT NULL, CHANGE is_mandatory is_mandatory TINYINT(1) NOT NULL, CHANGE display_order display_order INT NOT NULL');
        $this->addSql('ALTER TABLE expense_type_expense_field_type ADD CONSTRAINT FK_3996E5A7A857C7A9 FOREIGN KEY (expense_type_id) REFERENCES expense_type (id)');
        $this->addSql('ALTER TABLE expense_type_expense_field_type ADD CONSTRAINT FK_3996E5A766BD6C4D FOREIGN KEY (expense_field_type_id) REFERENCES expense_field_type (id)');
        $this->addSql('CREATE INDEX IDX_3996E5A7A857C7A9 ON expense_type_expense_field_type (expense_type_id)');
        $this->addSql('CREATE INDEX IDX_3996E5A766BD6C4D ON expense_type_expense_field_type (expense_field_type_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE expense DROP FOREIGN KEY FK_2D3A8DA6A857C7A9');
        $this->addSql('ALTER TABLE expense DROP FOREIGN KEY FK_2D3A8DA68F758FBA');
        $this->addSql('DROP INDEX IDX_2D3A8DA6A857C7A9 ON expense');
        $this->addSql('DROP INDEX IDX_2D3A8DA68F758FBA ON expense');
        $this->addSql('ALTER TABLE expense CHANGE spent_amount spent_amount INT NOT NULL COMMENT \'en centimes !\', CHANGE refund_amount refund_amount INT NOT NULL COMMENT \'en centimes !\', CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE expense_field DROP FOREIGN KEY FK_F8FDE262F395DB7B');
        $this->addSql('ALTER TABLE expense_field DROP FOREIGN KEY FK_F8FDE26266BD6C4D');
        $this->addSql('DROP INDEX IDX_F8FDE262F395DB7B ON expense_field');
        $this->addSql('DROP INDEX IDX_F8FDE26266BD6C4D ON expense_field');
        $this->addSql('ALTER TABLE expense_field CHANGE value value VARCHAR(255) DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE expense_field_type CHANGE input_type input_type VARCHAR(255) DEFAULT \'string\' NOT NULL');
        $this->addSql('ALTER TABLE expense_group CHANGE type type VARCHAR(255) DEFAULT \'raw\' NOT NULL');
        $this->addSql('ALTER TABLE expense_report DROP FOREIGN KEY FK_280A691A76ED395');
        $this->addSql('ALTER TABLE expense_report DROP FOREIGN KEY FK_280A69171F7E88B');
        $this->addSql('DROP INDEX IDX_280A691A76ED395 ON expense_report');
        $this->addSql('DROP INDEX IDX_280A69171F7E88B ON expense_report');
        $this->addSql('ALTER TABLE expense_report CHANGE user_id user_id INT NOT NULL, CHANGE refund_required refund_required TINYINT(1) DEFAULT 0 NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE expense_type DROP FOREIGN KEY FK_3879194B38351BBE');
        $this->addSql('DROP INDEX IDX_3879194B38351BBE ON expense_type');
        $this->addSql('ALTER TABLE expense_type CHANGE expense_group_id expense_group_id INT NOT NULL');
        $this->addSql('ALTER TABLE expense_type_expense_field_type DROP FOREIGN KEY FK_3996E5A7A857C7A9');
        $this->addSql('ALTER TABLE expense_type_expense_field_type DROP FOREIGN KEY FK_3996E5A766BD6C4D');
        $this->addSql('DROP INDEX IDX_3996E5A7A857C7A9 ON expense_type_expense_field_type');
        $this->addSql('DROP INDEX IDX_3996E5A766BD6C4D ON expense_type_expense_field_type');
        $this->addSql('ALTER TABLE expense_type_expense_field_type CHANGE needs_justification needs_justification TINYINT(1) DEFAULT 0 NOT NULL, CHANGE is_used_for_total is_used_for_total TINYINT(1) DEFAULT 0 NOT NULL, CHANGE is_mandatory is_mandatory TINYINT(1) DEFAULT 0 NOT NULL, CHANGE display_order display_order INT DEFAULT 0 NOT NULL');
    }
}
