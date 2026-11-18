<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261012090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Notes de frais : niveau de chaque décision sur une note hors sortie (responsable ou trésorerie)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expense_report_status_history ADD niveau VARCHAR(255) DEFAULT NULL');
        // Aucune note hors sortie en production. Ailleurs, seules les décisions dont le niveau se
        // lit dans l'historique sont reprises : un refus depuis l'envoi dépendait des valideurs du moment.
        $this->addSql("UPDATE expense_report_status_history h JOIN expense_report n ON n.id = h.expense_report_id
            SET h.niveau = IF(h.new_status = 'preapproved', 'responsable', 'tresorerie')
            WHERE n.type != 'sortie' AND (h.new_status = 'preapproved' OR (h.old_status = 'preapproved' AND h.new_status IN ('approved', 'rejected')))");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expense_report_status_history DROP niveau');
    }
}
