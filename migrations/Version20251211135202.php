<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251211135202 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'New administrable texts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            INSERT INTO `caf_content_html` (`code_content_html`, `lang_content_html`, `contenu_content_html`, `date_content_html`, `linkedtopage_content_html`, `current_content_html`, `vis_content_html`)
            VALUES ('commission-staff-skills', 'fr', '<p>Ces données sont issues de l''extranet FFCAM. Elles sont actualisées tous les x jours, le matin.<br>On peut visualiser ici les brevets (plus d''infos <a href="https://www.clubalpinlyon.fr/pages/formation.html">ici</a>), niveaux de pratiques (NP) et formations ainsi que deux formations générales de prérequis. On ne visualise pas ici les groupes de compétences (GC).<br>On ne visualise ici que ce qui a été défini en rapport avec l''encadrement dans l''activité où une personne a une responsabilité au club. On ne verra donc pas si un pratiquant a un brevet d''encadrement mais sans responsabilité dans ladite activité.<br>La date affichée est la date du dernier recyclage sinon la date de délivrance du brevet.<br><br>S''il n''y a pas de date, l''item  n''est pas acquis.</p>', UNIX_TIMESTAMP(), '', 1, 1);
        SQL);
        $this->addSql(<<<SQL
            INSERT INTO `caf_content_html` (`code_content_html`, `lang_content_html`, `contenu_content_html`, `date_content_html`, `linkedtopage_content_html`, `current_content_html`, `vis_content_html`)
            VALUES ('full-profile-skills', 'fr', '<p>Ces données sont issues de l''extranet FFCAM. Elles sont actualisées tous les x jours, le matin.<br>On peut visualiser ici les groupes de compétences (GC) (plus d''infos <a href="https://www.clubalpinlyon.fr/pages/formation.html">ici</a>) et les niveaux de pratiques (NP) ainsi que deux formations générales de prérequis. On ne visualise pas ici les brevets ni autres formations.<br><br>S''il n''y a pas de date, l''item  n''est pas acquis.</p>', UNIX_TIMESTAMP(), '', 1, 1);
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM caf_content_html WHERE code_content_html = \'commission-staff-skills\'');
        $this->addSql('DELETE FROM caf_content_html WHERE code_content_html = \'full-profile-skills\'');
    }
}
