<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251127110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Corrige contrainte UNIQUE sur formation_activite_commission : 1 code_activite = 1 commission';
    }

    public function up(Schema $schema): void
    {
        // Supprimer l'ancienne contrainte (code_activite, commission_id)
        $this->addSql('ALTER TABLE formation_activite_commission DROP INDEX UNIQ_FORM_ACT_COMM');
        // Supprimer l'index redondant
        $this->addSql('ALTER TABLE formation_activite_commission DROP INDEX IDX_FORM_ACT_COMM_ACTIVITE');
        // Ajouter la nouvelle contrainte : 1 code_activite = 1 commission
        $this->addSql('ALTER TABLE formation_activite_commission ADD UNIQUE INDEX UNIQ_FORM_ACT_COMM_CODE (code_activite)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE formation_activite_commission DROP INDEX UNIQ_FORM_ACT_COMM_CODE');
        $this->addSql('ALTER TABLE formation_activite_commission ADD INDEX IDX_FORM_ACT_COMM_ACTIVITE (code_activite)');
        $this->addSql('ALTER TABLE formation_activite_commission ADD UNIQUE INDEX UNIQ_FORM_ACT_COMM (code_activite, commission_id)');
    }
}
