<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211128163652 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user_attr DROP FOREIGN KEY FK_67322AB88BE7C3B3');
        $this->addSql('DROP INDEX fk_67322ab88be7c3b3 ON caf_user_attr');
        $this->addSql('CREATE INDEX IDX_67322AB88BE7C3B3 ON caf_user_attr (usertype_user_attr)');
        $this->addSql('ALTER TABLE caf_user_attr ADD CONSTRAINT FK_67322AB88BE7C3B3 FOREIGN KEY (usertype_user_attr) REFERENCES caf_usertype (id_usertype)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user_attr DROP FOREIGN KEY FK_67322AB88BE7C3B3');
        $this->addSql('DROP INDEX idx_67322ab88be7c3b3 ON caf_user_attr');
        $this->addSql('CREATE INDEX FK_67322AB88BE7C3B3 ON caf_user_attr (usertype_user_attr)');
        $this->addSql('ALTER TABLE caf_user_attr ADD CONSTRAINT FK_67322AB88BE7C3B3 FOREIGN KEY (usertype_user_attr) REFERENCES caf_usertype (id_usertype)');
    }
}
