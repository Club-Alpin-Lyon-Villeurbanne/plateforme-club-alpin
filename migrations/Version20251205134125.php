<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251205134125 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'New administrable text';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            INSERT INTO `caf_content_html` (`code_content_html`, `lang_content_html`, `contenu_content_html`, `date_content_html`, `linkedtopage_content_html`, `current_content_html`, `vis_content_html`)
            VALUES ('password-lost-intro', 'fr', '<p>Entrez ci-dessous l''adresse e-mail associée à votre espace licencié FFCAM.<br>
                Vous recevrez un courrier avec un lien sur lequel cliquer pour créer ou ré-initialiser votre mot de passe.</p>', UNIX_TIMESTAMP(), '', 1, 1);
        SQL);
        $this->addSql(<<<SQL
            INSERT INTO `caf_content_html` (`code_content_html`, `lang_content_html`, `contenu_content_html`, `date_content_html`, `linkedtopage_content_html`, `current_content_html`, `vis_content_html`)
            VALUES ('password-lost-confirm', 'fr', '<p>Vous n''avez pas reçu l''e-mail ? Assurez-vous d''avoir bien indiqué l''adresse e-mail associée à votre espace licencié FFCAM et d''avoir regardé dans vos messages indésirables.<br>
                <a href="/password-lost">Essayez une autre adresse e-mail</a> ou <a href="https://forms.clickup.com/42653954/f/18np82-775/1BKP6TIKU0RIYXCRWE">contactez-nous</a>.</p>', UNIX_TIMESTAMP(), '', 1, 1);
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM caf_content_html WHERE code_content_html = \'password-lost-intro\'');
        $this->addSql('DELETE FROM caf_content_html WHERE code_content_html = \'password-lost-confirm\'');
    }
}
