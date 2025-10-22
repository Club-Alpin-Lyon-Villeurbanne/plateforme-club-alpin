<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251022125600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Updates tables structure';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE formation_brevet DROP FOREIGN KEY FK_BREVET_REF');
        $this->addSql('DROP INDEX idx_code_brevet ON formation_brevet');
        $this->addSql('ALTER TABLE formation_brevet ADD brevet_id INT NOT NULL, DROP code_brevet');
        $this->addSql('ALTER TABLE formation_brevet ADD CONSTRAINT FK_44938B645293752B FOREIGN KEY (brevet_id) REFERENCES formation_brevet_referentiel (id)');
        $this->addSql('CREATE INDEX idx_brevet_id ON formation_brevet (brevet_id)');
        $this->addSql('ALTER TABLE formation_brevet RENAME INDEX idx_brevet_user TO IDX_44938B64A76ED395');
        $this->addSql('ALTER TABLE formation_brevet_referentiel RENAME INDEX idx_code_brevet TO UNIQ_CODE_BREVET');
        $this->addSql('DROP INDEX IDX_FORM_NIV_REF_CURSUS ON formation_niveau_referentiel');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE formation_brevet DROP FOREIGN KEY FK_44938B645293752B');
        $this->addSql('DROP INDEX idx_brevet_id ON formation_brevet');
        $this->addSql('ALTER TABLE formation_brevet ADD code_brevet VARCHAR(50) NOT NULL, DROP brevet_id');
        $this->addSql('ALTER TABLE formation_brevet ADD CONSTRAINT FK_BREVET_REF FOREIGN KEY (code_brevet) REFERENCES formation_brevet_referentiel (code_brevet) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX idx_code_brevet ON formation_brevet (code_brevet)');
        $this->addSql('ALTER TABLE formation_brevet RENAME INDEX idx_44938b64a76ed395 TO IDX_BREVET_USER');
        $this->addSql('ALTER TABLE formation_brevet_referentiel RENAME INDEX uniq_code_brevet TO idx_code_brevet');
        $this->addSql('CREATE INDEX IDX_FORM_NIV_REF_CURSUS ON formation_niveau_referentiel (cursus_niveau_id)');
    }
}
