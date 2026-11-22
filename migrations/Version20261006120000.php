<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261006120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adhérents : ramène les anciennes civilités (M, MME, MLLE…) au sexe livré par la FFCAM depuis fin août 2026 (Masculin, Féminin, Autre)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE caf_user CHANGE civ_user civ_user VARCHAR(10) DEFAULT NULL COMMENT 'Sexe tel que fourni par la FFCAM : Masculin, Féminin ou Autre'");
        // Collation insensible à la casse : 'MME' couvre aussi 'Mme'.
        $this->addSql("UPDATE caf_user SET civ_user = 'Masculin' WHERE civ_user IN ('M', 'M.')");
        $this->addSql("UPDATE caf_user SET civ_user = 'Féminin' WHERE civ_user IN ('MME', 'MME.', 'MLLE', 'MLLE.')");
        $this->addSql("UPDATE caf_user SET civ_user = NULL WHERE TRIM(civ_user) = ''");
    }

    public function down(Schema $schema): void
    {
        // Les anciennes valeurs ne sont pas restaurées : on ne sait plus qui était « MLLE ».
        $this->addSql('ALTER TABLE caf_user CHANGE civ_user civ_user VARCHAR(10) DEFAULT NULL');
    }
}
