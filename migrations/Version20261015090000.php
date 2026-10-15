<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Compétences FFCAM : les textes d\'explication annoncent une actualisation chaque semaine';
    }

    public function up(Schema $schema): void
    {
        // Seule la phrase de fréquence est remplacée : le reste du texte a pu être retouché depuis l'admin.
        foreach (['tous les mois.', 'tous les x jours, le matin.'] as $ancienneFrequence) {
            $this->addSql(
                "UPDATE caf_content_html
                 SET contenu_content_html = REPLACE(contenu_content_html, :ancienne, :nouvelle)
                 WHERE current_content_html = 1
                   AND code_content_html IN ('commission-staff-skills', 'full-profile-skills', 'event-skills')",
                [
                    'ancienne' => 'Elles sont actualisées ' . $ancienneFrequence,
                    'nouvelle' => 'Elles sont actualisées chaque semaine.',
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
        // L'ancienne phrase n'est pas restaurée : elle différait d'un texte et d'un club à l'autre.
    }
}
