<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211204001236 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt CHANGE status_who_evt status_who_evt INT DEFAULT NULL COMMENT \'ID de l\'\'user qui a changé le statut en dernier\', CHANGE status_legal_who_evt status_legal_who_evt INT DEFAULT NULL COMMENT \'ID du validateur légal\', CHANGE cancelled_evt cancelled_evt TINYINT(1) DEFAULT \'0\' NOT NULL, CHANGE cancelled_who_evt cancelled_who_evt INT DEFAULT NULL COMMENT \'ID user qui a  annulé l\'\'evt\', CHANGE cancelled_when_evt cancelled_when_evt BIGINT DEFAULT NULL COMMENT \'Timestamp annulation\', CHANGE tsp_edit_evt tsp_edit_evt BIGINT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt CHANGE status_who_evt status_who_evt INT NOT NULL COMMENT \'ID de l\'\'user qui a changé le statut en dernier\', CHANGE status_legal_who_evt status_legal_who_evt INT NOT NULL COMMENT \'ID du validateur légal\', CHANGE cancelled_evt cancelled_evt TINYINT(1) NOT NULL, CHANGE cancelled_who_evt cancelled_who_evt INT NOT NULL COMMENT \'ID user qui a  annulé l\'\'evt\', CHANGE cancelled_when_evt cancelled_when_evt BIGINT NOT NULL COMMENT \'Timestamp annulation\', CHANGE tsp_edit_evt tsp_edit_evt BIGINT NOT NULL');
    }
}
