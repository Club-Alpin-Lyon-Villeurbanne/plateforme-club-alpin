<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260903050000 extends AbstractMigration
{
    private const CODE = 'methodologie-bilan-carbone';

    public function getDescription(): string
    {
        return 'Bilan carbone : amorce le contenu éditable de la page méthodologie';
    }

    public function up(Schema $schema): void
    {
        // Le contenu est ensuite maintenu par la commission environnement via le CMS.
        // INSERT conditionnel : ne rien écraser si le bloc a déjà été créé à la main.
        $this->addSql(
            'INSERT INTO caf_content_html
                (code_content_html, lang_content_html, contenu_content_html, date_content_html, linkedtopage_content_html, current_content_html, vis_content_html)
             SELECT :code, :lang, :contenu, UNIX_TIMESTAMP(), :page, 1, 1
             FROM DUAL
             WHERE NOT EXISTS (SELECT 1 FROM caf_content_html WHERE code_content_html = :code)',
            [
                'code' => self::CODE,
                'lang' => 'fr',
                'contenu' => self::contenu(),
                'page' => '/sorties/methodologie-bilan-carbone',
            ]
        );
    }

    public function down(Schema $schema): void
    {
        // Supprime aussi les révisions saisies depuis le CMS : c'est le prix d'un rollback
        // sur un contenu éditorial, il n'y a pas d'état antérieur à restaurer.
        $this->addSql('DELETE FROM caf_content_html WHERE code_content_html = :code', ['code' => self::CODE]);
    }

    private static function contenu(): string
    {
        return <<<'HTML'
            <p>Le club s'est engagé, par la charte environnement votée à l'assemblée générale de janvier 2026, à mettre en place des outils de mesure de nos émissions de CO2. Les bénévoles des commissions environnement et numérique ont travaillé ensemble pour avoir un outil de calcul automatique du bilan carbone de la partie transport d'une sortie dès son dépôt.</p>

            <h2 class="bleucaf">Objectifs</h2>
            <ul>
                <li>Permettre aux adhérents de connaître l'impact carbone généré par le transport lors d'une sortie dès sa publication</li>
                <li>Faciliter la mise à jour du bilan carbone du club, et le suivi annuel d'indicateurs sur l'impact du transport</li>
                <li>À terme, permettre à un adhérent de voir son impact carbone individuel sur l'année pour les sorties club</li>
            </ul>

            <h2 class="bleucaf">Méthodologie</h2>
            <p>L'impact carbone d'une sortie est calculé de la façon suivante :</p>
            <p style="background:#f0f7f0; border-left:4px solid #4a9c5b; padding:12px 16px;"><em>Impact total (kg CO2) = distance (km) &times; nombre de véhicules &times; facteur d'émission d'un véhicule (kg CO2/km)</em></p>
            <p>L'impact par personne est calculé en fonction du nombre maximum théorique de participants : d'une part la plupart de nos sorties sont complètes lorsqu'elles ont lieu, d'autre part c'est le seul moyen d'éviter un recalcul à chaque inscription.</p>

            <h2 class="bleucaf">Calcul du nombre de km</h2>
            <ul>
                <li><strong>Lieu de départ :</strong> on utilise le point GPS défini lors du dépôt de la sortie.</li>
                <li><strong>Lieu d'arrivée :</strong>
                    <ul>
                        <li>La commune de pratique de l'activité est définie lors du dépôt de la sortie.</li>
                        <li>On définit le point GPS au niveau de la mairie de la commune grâce à la liste <a href="https://www.data.gouv.fr/datasets/communes-et-villes-de-france-en-csv-excel-json-parquet-et-feather/" target="_blank" rel="noopener">Communes et villes de France de data.gouv.fr</a>, stockée dans notre base.</li>
                        <li>Il s'agit d'une approximation du lieu réel de l'activité, suffisante dans la perspective d'un bilan carbone.</li>
                    </ul>
                </li>
                <li>Ensuite on calcule le nombre de km « par la route » avec l'API <a href="https://project-osrm.org/" target="_blank" rel="noopener">OSRM</a>, libre et gratuite.</li>
            </ul>

            <h2 class="bleucaf">Modes de transport</h2>
            <p>Sélection parmi :</p>
            <ul>
                <li>🚅 Transports en commun ferroviaire</li>
                <li>🚌 Transports en commun routier</li>
                <li>🚌 Car affrété</li>
                <li>🚐 Minibus</li>
                <li>🚗 Covoiturage thermique</li>
                <li>🚙 Covoiturage électrique</li>
                <li>🚲 Vélo / pédestre</li>
            </ul>
            <p><strong>Nombre de véhicules :</strong> pour les modes Car affrété, Minibus, Covoiturage électrique et Covoiturage thermique, l'encadrant définit le nombre de véhicules lors du dépôt de la sortie.</p>
            <ul>
                <li>En V1, on ne peut sélectionner qu'un seul type de véhicule. <strong>L'encadrant qui dépose la sortie sélectionne celui qui est le plus représentatif.</strong></li>
                <li>En V2, on pourra prévoir la sélection d'un nombre pour chaque type de véhicule.</li>
            </ul>

            <h2 class="bleucaf">Facteurs d'émission</h2>
            <ul>
                <li>Transport en commun ferroviaire : 10 g/km/participant</li>
                <li>Transport en commun routier : 122 g/km/participant</li>
                <li>Car affrété : 25 g/km/passager, soit 870 g/km/véhicule</li>
                <li>Covoiturage thermique : 219 g/km/véhicule</li>
                <li>Covoiturage électrique : 102 g/km/véhicule</li>
                <li>Minibus : 281 g/km/véhicule</li>
            </ul>
            <p>Les facteurs d'émission sont issus du site <a href="https://impactco2.fr/outils/transport" target="_blank" rel="noopener">impactco2.fr</a>, sauf pour le minibus et le ferroviaire.</p>

            <h3>Ferroviaire</h3>
            <p>Le choix est fait d'utiliser un facteur d'émission de 10 g CO2e/km/passager. L'empreinte affichée par l'Ademe varie entre 2,93 g CO2e/km/passager pour le TGV, 9,78 pour les Intercités et 27,7 pour le TER. Pourquoi une telle différence ? Le facteur d'émission du TER regroupe des choses très différentes : des TER électriques, des TER thermiques, et même des cars TER au bilan carbone assez mauvais. Nous retenons le facteur des Intercités pour les raisons suivantes :</p>
            <ul>
                <li>les trajets réalisés en train lors de nos déplacements correspondent régulièrement à d'anciens trajets Intercités (matériel roulant compris), qui ont presque disparu du paysage ferroviaire pour être remplacés par des TER depuis la régionalisation ;</li>
                <li>la plupart des lignes passager que nous empruntons dans la région sont électrifiées, à l'exception notable, pour nos trajets favoris, de Saint-Étienne &ndash; Le Puy et Valence &ndash; Briançon ;</li>
                <li>les trajets en car sont pris en compte de façon séparée dans le transport en commun routier ou le car affrété.</li>
            </ul>

            <h3>Minibus</h3>
            <p>En l'absence de données pour un minibus 9 places, l'impact est recalculé à partir des données et hypothèses de l'Ademe :</p>
            <ul>
                <li>Impact minibus par km = impact fabrication / km + (consommation / 100) &times; impact carburant / l = 0,038 + (8 / 100) &times; 3,04 = 0,2812 kg CO2e/km, soit 281 g CO2e/km</li>
                <li>Impact fabrication / km = masse du véhicule &times; 4 kg CO2e/kg / 200 000 km (hypothèse standard Ademe) = 1 900 &times; 4 / 200 000 = 0,038 kg CO2e/km</li>
                <li>Consommation : 8 l/100 km avec une conduite douce (11 l si on roule à 130 !)</li>
                <li>Impact carburant / l pour le gazole : 3,04 kg CO2e/l (incluant le cycle de production du carburant)</li>
            </ul>
            HTML;
    }
}
