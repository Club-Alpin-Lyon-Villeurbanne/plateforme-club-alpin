<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250908132603 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            INSERT INTO `caf_content_html` (`code_content_html`, `lang_content_html`, `contenu_content_html`, `date_content_html`, `linkedtopage_content_html`, `current_content_html`, `vis_content_html`)
            VALUES ('local-club-description', 'fr', '<p>Il est possible de r&eacute;server le local du Club pour des activit&eacute;s li&eacute;es au fonctionnement du Club.</p>
            <p>Elle peut contenir environ 30 personnes assises (formation, pr&eacute;sentation, r&eacute;unions) et environ 70 debout.</p>
            <p>R&egrave;gles de r&eacute;servation du local du Club / Priorit&eacute;s&nbsp;:</p>
            <ol start="1" style="list-style-type:decimal">
                <li>Jeudi&nbsp;: r&eacute;serv&eacute; &agrave; l&rsquo;accueil, inscriptions et &eacute;v&egrave;nements festifs</li>
                <li>R&eacute;unions institutionnelles&nbsp;: Comit&eacute; Directeur et Bureau</li>
                <li>R&eacute;unions de Commissions</li>
                <li>Formations inscrites sur le site</li>
                <li>Autres soir&eacute;es&nbsp;: pr&eacute;parations de raid, sorties, s&eacute;jours &hellip;</li>
            </ol>
            <p>Dans la mesure du possible, informer au moins deux semaines avant la r&eacute;union les personnes ayant d&eacute;j&agrave; r&eacute;serv&eacute; afin qu&rsquo;elles puissent trouver une autre date.<br/>
            Au sein d&rsquo;un m&ecirc;me niveau, c&rsquo;est la premi&egrave;re inscription qui pr&eacute;vaut.</p>
            <p>Rappel : la salle mat&eacute;riel peut &ecirc;tre une salle de repli pour un petit groupe.</p>
            <p>Merci de quitter la salle rang&eacute;e et nettoy&eacute;e.</p>
            <p>Pour faire une demande de r&eacute;servation, veuillez remplir ce formulaire: <a href="https://forms.gle/ZvsHy3wsu4H2vpFk9" target="_blank">https://forms.gle/ZvsHy3wsu4H2vpFk9</a></p>
            <p>Pour annuler une demande de réservation, contactez <a href="mailto:accueil@clubalpinlyon.fr">accueil@clubalpinlyon.fr</a></p>
            <p>Dans tous les cas, l&#39;agenda du local, pr&eacute;sent ci-dessous, fait foi.</p>', UNIX_TIMESTAMP(), '', 1, 1);
        SQL);
        $this->addSql(<<<SQL
            INSERT INTO `caf_content_html` (`code_content_html`, `lang_content_html`, `contenu_content_html`, `date_content_html`, `linkedtopage_content_html`, `current_content_html`, `vis_content_html`)
            VALUES ('minivan-presentation', 'fr', '<p>Un minibus est mis à disposition des encadrants et bénévoles du club pour une utilisation exclusive dans le cadre des sorties</p>
            <p>
                L\'utilisation du minibus requiert de la part de ses usagers :
            </p>
            <ul>
                <li>l\'acceptation de la <a href="https://docs.google.com/document/d/1MyctEQEK9DXWeEhhaMKnjx2mIFrtSTu8wJLxRx-gsP8/edit" target="_blank">charte de fonctionnement du minibus</a></li>
                <li>une participation financière</li>
            </ul>', UNIX_TIMESTAMP(), '', 1, 1);
        SQL);
        $this->addSql(<<<SQL
            INSERT INTO `caf_content_html` (`code_content_html`, `lang_content_html`, `contenu_content_html`, `date_content_html`, `linkedtopage_content_html`, `current_content_html`, `vis_content_html`)
            VALUES ('minivan-reservation', 'fr', '<h1><span class="bleucaf">Réservation</span></h1>
            <p>
                Etape 1: <a href="https://docs.google.com/document/d/1MyctEQEK9DXWeEhhaMKnjx2mIFrtSTu8wJLxRx-gsP8/edit" target="_blank">Lire la charte</a>
            </p>
            <p>
                Etape 2: <a href="https://forms.gle/7evfr5YcTXupVAPK6" target="_blank">Accepter la charte</a>
            </p>
            <p>
                Etape 3: <a href="https://forms.gle/viKQybTHPQ7zoeRFA" target="_blank">Faire la demande de réservation</a>
            </p>
            <p>
                Etape 4: <a href="https://forms.gle/xfcBBCpvKv2MGh7w8" target="_blank">Remplir le formulaire de restitution</a>
            </p>
            <p>
                Etape 5: <a href="https://www.helloasso.com/associations/club-alpin-francais-de-lyon-villeurbanne/evenements/remboursement-location-minibus-caf-lyon" target="_blank">Payer vos frais uniquement via Hello Asso</a>
            </p>
            <p>
                Pour tout renseignement, commentaire ou problème, contactez <a href="mailto:minibus@clubalpinlyon.fr">minibus@clubalpinlyon.fr</a>.
            </p>', UNIX_TIMESTAMP(), '', 1, 1);
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM caf_content_html WHERE code_content_html = \'local-club-description\'');
    }
}
