<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251128142134 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renames tables to have a proper naming logic';
    }

    public function up(Schema $schema): void
    {
        // référentiels
        $this->addSql('RENAME TABLE formation_brevet_referentiel TO formation_referentiel_brevet');
        $this->addSql('RENAME TABLE formation_referentiel TO formation_referentiel_formation');
        $this->addSql('RENAME TABLE formation_competence_referentiel TO formation_referentiel_groupe_competence');
        $this->addSql('RENAME TABLE formation_niveau_referentiel TO formation_referentiel_niveau_pratique');

        // validations par les users
        $this->addSql('RENAME TABLE formation_brevet TO formation_validation_brevet');
        $this->addSql('RENAME TABLE formation_validation TO formation_validation_formation');
        $this->addSql('RENAME TABLE formation_competence_validation TO formation_validation_groupe_competence');
        $this->addSql('RENAME TABLE formation_niveau_validation TO formation_validation_niveau_pratique');

        // jointures avec commission
        $this->addSql('RENAME TABLE formation_brevet_commission TO formation_commission_brevet');
        $this->addSql('RENAME TABLE formation_commission TO formation_commission_formation');
        $this->addSql('RENAME TABLE groupe_competence_commission TO formation_commission_groupe_competence');
        $this->addSql('RENAME TABLE niveau_commission TO formation_commission_niveau_pratique');
    }

    public function down(Schema $schema): void
    {
    }
}
