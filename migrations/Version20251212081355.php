<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251212081355 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'New administrable text';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            INSERT INTO `caf_content_html` (`code_content_html`, `lang_content_html`, `contenu_content_html`, `date_content_html`, `linkedtopage_content_html`, `current_content_html`, `vis_content_html`)
            VALUES ('event-skills', 'fr', '<p>Ces données sont issues de l''extranet FFCAM. Elles sont actualisées tous les x jours, le matin.<br>On peut visualiser ici les groupes de compétences (GC), les niveaux de pratiques (NP) ainsi que deux formations générales de prérequis  (plus d''infos sur la formation <a href="https://www.clubalpinlyon.fr/pages/formation.html">ici</a>). On ne visualise ici que ce qui est en rapport avec l''activité de la sortie.<br>La date qui est visualisée est la date du dernier recyclage sinon la date de délivrance du brevet.<br><br>S''il n''y a pas de date, l''item  n''est pas acquis.</p>', UNIX_TIMESTAMP(), '', 1, 1);
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM caf_content_html WHERE code_content_html = \'event-skills\'');
    }
}
