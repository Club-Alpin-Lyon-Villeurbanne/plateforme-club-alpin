<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250731120830 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds new tables to store FFCAM skills and levels for users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_competence CHANGE titre titre_competence VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_code_competence ON caf_competence (code_competence)');
        $this->addSql('ALTER TABLE caf_niveau_pratique ADD code_competence VARCHAR(15) NOT NULL');
        $this->addSql('CREATE INDEX idx_code_competence ON caf_niveau_pratique (code_competence)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_code_competence ON caf_competence');
        $this->addSql('ALTER TABLE caf_competence CHANGE titre_competence titre VARCHAR(255) DEFAULT NULL');
        $this->addSql('DROP INDEX idx_code_competence ON caf_niveau_pratique');
        $this->addSql('ALTER TABLE caf_niveau_pratique DROP code_competence');
    }
}
