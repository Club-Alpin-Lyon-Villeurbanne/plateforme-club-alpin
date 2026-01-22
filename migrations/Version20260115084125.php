<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260115084125 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'New administrable text';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            INSERT INTO `caf_content_html` (`code_content_html`, `lang_content_html`, `contenu_content_html`, `date_content_html`, `linkedtopage_content_html`, `current_content_html`, `vis_content_html`)
            VALUES ('user_create', 'fr', '<p>Depuis cette page, vous pouvez créer une nouvelle entrée dans la base de données des membres du site. Prenez bien soin d''entrer une adresse e-mail valide et une date de naissance correcte !</p><p>Une fois l''utilisateur créé, rendez-vous sur la <a href="/adherents" title="" target="_top">page adhérents</a> pour lui attribuer les rôles désirés (exemple : <i>salarié</i>) en cliquant sur le bouton <img src="/img/base/user_star.png" alt="" title="" />.</p>', UNIX_TIMESTAMP(), '', 1, 1);
        SQL);
        $this->addSql(<<<SQL
            INSERT INTO `caf_content_html` (`code_content_html`, `lang_content_html`, `contenu_content_html`, `date_content_html`, `linkedtopage_content_html`, `current_content_html`, `vis_content_html`)
            VALUES ('user_update', 'fr', '<p>Depuis cette page, vous pouvez modifier une entrée dans la base de données des membres du site. Prenez bien soin d''entrer une adresse e-mail valide !</p>', UNIX_TIMESTAMP(), '', 1, 1);
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM caf_content_html WHERE code_content_html = \'user_create\'');
        $this->addSql('DELETE FROM caf_content_html WHERE code_content_html = \'user_update\'');
    }
}
