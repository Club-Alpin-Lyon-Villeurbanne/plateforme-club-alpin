<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250602124204 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE caf_userright SET title_userright = 'Valider l\'inscription d\'un participant (adhérent tiers) au sein d\'une commission' WHERE code_userright = 'evt_joining_accept'");
        $this->addSql("UPDATE caf_userright SET title_userright = 'Refuser l\'inscription d\'un participant (adhérent tiers) au sein d\'une commission' WHERE code_userright = 'evt_joining_refuse'");
        $this->addSql("UPDATE caf_userright SET title_userright = 'Désinscrire un participant (adhérent tiers) au sein d\'une commission' WHERE code_userright = 'evt_unjoin_notme'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE caf_userright SET title_userright = 'Valider l\'inscription d\'un participant (adhérent tiers)' WHERE code_userright = 'evt_joining_accept'");
        $this->addSql("UPDATE caf_userright SET title_userright = 'Refuser l\'inscription d\'un participant (adhérent tiers)' WHERE code_userright = 'evt_joining_refuse'");
        $this->addSql("UPDATE caf_userright SET title_userright = 'Désinscrire un participant (adhérent tiers)' WHERE code_userright = 'evt_unjoin_notme'");
    }
}
