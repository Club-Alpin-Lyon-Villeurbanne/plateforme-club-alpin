<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211228110110 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_article CHANGE id_article id_article INT AUTO_INCREMENT NOT NULL');
        $this->addSql('UPDATE caf_evt SET id_groupe = null WHERE id_groupe NOT IN (SELECT id FROM caf_groupe)');
        $this->addSql('ALTER TABLE caf_evt ADD CONSTRAINT FK_197AA7E228E39CC FOREIGN KEY (id_groupe) REFERENCES caf_groupe (id)');
        $this->addSql('CREATE INDEX IDX_197AA7E228E39CC ON caf_evt (id_groupe)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_article CHANGE id_article id_article INT UNSIGNED AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE caf_evt DROP FOREIGN KEY FK_197AA7E228E39CC');
        $this->addSql('DROP INDEX IDX_197AA7E228E39CC ON caf_evt');
    }
}
