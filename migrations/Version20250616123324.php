<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250616123324 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            UPDATE caf_content_html SET contenu_content_html = '<p>Cliquez ci-dessous pour placer le marqueur sur la carte, puis déplacez ce dernier sur le <span style="text-decoration: underline;">lieu exact du RDV</span>. Vous pouvez zoomer / dézoomer.</p>' WHERE code_content_html = 'infos-carte';
            UPDATE caf_content_html SET contenu_content_html = '<p>Ville et adresse du lieu de RDV pour vous rendre à la sortie. Ce champ permet de placer le marqueur sur la carte.</p>' WHERE code_content_html = 'infos-lieu-de-rdv';
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<SQL
            UPDATE caf_content_html SET contenu_content_html = '<p>Cliquez ci-dessous pour placer le point sur la carte, puis déplacez ce dernier sur le <span style="text-decoration: underline;">lieu exact du RDV</span>. Vous pouvez zoomer / dézoomer.</p>' WHERE code_content_html = 'infos-carte';
            UPDATE caf_content_html SET contenu_content_html = '<p>Ville, et adresse du lieu de RDV pour vous rendre à la sortie. Ce champ permet de placer automatiquement le point sur la carte.</p>' WHERE code_content_html = 'infos-lieu-de-rdv';
        SQL);
    }
}
