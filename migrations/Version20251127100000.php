<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251127100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute table liaison brevets FFCAM ↔ commissions club';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE formation_brevet_commission (
                brevet_id INT NOT NULL,
                commission_id INT NOT NULL,
                PRIMARY KEY(brevet_id, commission_id),
                INDEX IDX_BREV_COMM_BREVET (brevet_id),
                INDEX IDX_BREV_COMM_COMMISSION (commission_id),
                CONSTRAINT FK_BREV_COMM_BREVET FOREIGN KEY (brevet_id)
                    REFERENCES formation_brevet_referentiel (id) ON DELETE CASCADE,
                CONSTRAINT FK_BREV_COMM_COMMISSION FOREIGN KEY (commission_id)
                    REFERENCES caf_commission (id_commission) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE formation_brevet_commission');
    }
}
