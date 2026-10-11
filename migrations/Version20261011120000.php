<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261011120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Notes de frais hors sortie (formation, achat) : type de note, sortie facultative, commission, objet, date de la dépense, lien vers la formation, mot pour le responsable, totaux figés, choix entre don et remboursement facultatif';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE expense_report ADD type VARCHAR(20) DEFAULT 'sortie' NOT NULL, ADD commission_id INT DEFAULT NULL, ADD objet VARCHAR(255) DEFAULT NULL, ADD date_depense DATE DEFAULT NULL COMMENT '(DC2Type:date_immutable)', ADD lien_formation VARCHAR(255) DEFAULT NULL, ADD mot_pour_responsable LONGTEXT DEFAULT NULL, ADD total_declare NUMERIC(10, 2) DEFAULT NULL, ADD total_remboursable NUMERIC(10, 2) DEFAULT NULL, CHANGE event_id event_id INT DEFAULT NULL, CHANGE refund_required refund_required TINYINT(1) DEFAULT NULL");
        $this->addSql('ALTER TABLE expense_report ADD CONSTRAINT FK_280A691202D1EB2 FOREIGN KEY (commission_id) REFERENCES caf_commission (id_commission)');
        $this->addSql('CREATE INDEX IDX_280A691202D1EB2 ON expense_report (commission_id)');
    }

    public function down(Schema $schema): void
    {
        // Échoue tant qu'il reste des notes hors sortie : elles n'ont ni sortie ni, parfois, de choix entre don et remboursement.
        $this->addSql('ALTER TABLE expense_report DROP FOREIGN KEY FK_280A691202D1EB2');
        $this->addSql('DROP INDEX IDX_280A691202D1EB2 ON expense_report');
        $this->addSql('ALTER TABLE expense_report DROP type, DROP commission_id, DROP objet, DROP date_depense, DROP lien_formation, DROP mot_pour_responsable, DROP total_declare, DROP total_remboursable, CHANGE event_id event_id INT NOT NULL, CHANGE refund_required refund_required TINYINT(1) NOT NULL');
    }
}
