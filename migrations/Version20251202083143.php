<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251202083143 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Changes join column and indexes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE formation_validation_formation DROP FOREIGN KEY FK_FORM_VAL_REF');
        $this->addSql('DROP INDEX IDX_FORM_VAL_CODE ON formation_validation_formation');
        $this->addSql('ALTER TABLE formation_validation_formation ADD formation_id INT NOT NULL AFTER user_id');
        $this->addSql('ALTER TABLE formation_validation_formation ADD CONSTRAINT FK_5C4C6C745200282E FOREIGN KEY (formation_id) REFERENCES formation_referentiel_formation (id) ON DELETE RESTRICT');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FORM_VAL_USER_FORMATION ON formation_validation_formation (user_id, formation_id)');
        $this->addSql('CREATE INDEX IDX_FORM_VAL_ID ON formation_validation_formation (formation_id)');
        $this->addSql('ALTER TABLE formation_validation_formation DROP code_formation');
        $this->addSql('DROP INDEX UNIQ_FORM_VAL_USER_ID_INTERNE ON formation_validation_formation');
        $this->addSql('DROP INDEX IDX_FORM_COMP_VAL_VALID ON formation_validation_groupe_competence');
    }

    public function down(Schema $schema): void
    {
    }
}
