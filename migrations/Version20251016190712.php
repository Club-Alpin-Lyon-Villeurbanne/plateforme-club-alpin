<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251016190712 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des tables formation_brevet_referentiel et formation_brevet pour gérer les brevets des adhérents';
    }

    public function up(Schema $schema): void
    {
        // Table de référence des brevets
        $this->addSql('CREATE TABLE formation_brevet_referentiel (code_brevet VARCHAR(50) NOT NULL, intitule VARCHAR(255) NOT NULL, PRIMARY KEY(code_brevet)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Table d'association brevets/adhérents
        $this->addSql('CREATE TABLE formation_brevet (id INT AUTO_INCREMENT NOT NULL, user_id BIGINT NOT NULL, cafnum_user VARCHAR(20) NOT NULL, code_brevet VARCHAR(50) NOT NULL, date_obtention DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', date_recyclage DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', date_edition DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', date_formation_continue DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', date_migration DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_BREVET_USER (user_id), INDEX idx_cafnum (cafnum_user), INDEX idx_code_brevet (code_brevet), INDEX idx_date_obtention (date_obtention), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Foreign keys
        $this->addSql('ALTER TABLE formation_brevet ADD CONSTRAINT FK_BREVET_USER FOREIGN KEY (user_id) REFERENCES caf_user (id_user)');
        $this->addSql('ALTER TABLE formation_brevet ADD CONSTRAINT FK_BREVET_REF FOREIGN KEY (code_brevet) REFERENCES formation_brevet_referentiel (code_brevet)');

        // Dernière synchro
        $this->addSql('INSERT INTO formation_last_sync (type) VALUES (\'brevets\');');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE formation_brevet DROP FOREIGN KEY FK_BREVET_USER');
        $this->addSql('ALTER TABLE formation_brevet DROP FOREIGN KEY FK_BREVET_REF');
        $this->addSql('DROP TABLE formation_brevet');
        $this->addSql('DROP TABLE formation_brevet_referentiel');
        $this->addSql('DELETE FROM formation_last_sync WHERE type = \'brevets\'');
    }
}
