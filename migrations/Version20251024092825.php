<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251024092825 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Restores valid_user as pure boolean field and removes user right to change it to value = 2';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user CHANGE valid_user valid_user TINYINT(1) NOT NULL COMMENT \'0=l\'\'user n\'\'a pas activé son compte   1=activé\'');
        $this->addSql('DELETE FROM caf_userright WHERE `code_userright` = \'user_desactivate_any\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user CHANGE valid_user valid_user TINYINT(1) NOT NULL COMMENT \'0=l\'\'user n\'\'a pas activé son compte   1=activé    2=bloqué\'');
        $this->addSql(<<<SQL
            INSERT INTO `caf_userright` (`code_userright`, `title_userright`, `ordre_userright`, `parent_userright`) VALUES ('user_desactivate_any', 'Désactiver un compte adhérent (compte conservé en bdd)', '340', 'GESTION DES COMPTES ADHERENTS');
        SQL);
    }
}
