<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250829130633 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            INSERT INTO `caf_content_html` (`code_content_html`, `lang_content_html`, `contenu_content_html`, `date_content_html`, `linkedtopage_content_html`, `current_content_html`, `vis_content_html`)
            VALUES ('materiel-explanation', 'fr', '<p>
                Le club dispose de certains matériels pour vous aider à accéder aux activités.
            </p>
            <p>
                Il vous est possible d''en louer via notre plateforme de réservation Loxya.
            </p>
            <br>

            <p>
                Tout d''abord, quelques règles :
            </p>
            <div class="p-4 mb-6 border-l-4 border-blue-500 bg-blue-50">
                <div class="flex">
                    <div class="ml-3">
                        <ul class="text-sm text-blue-700">
                            <li>Seuls les adhérents du club alpin français de Lyon Villeurbanne à jour de leur licence peuvent emprunter et réserver le matériel du club via notre plateforme de réservation Loxya. Ce n''est pas possible avec des cartes découvertes.</li>
                            <li>Il n''est pas possible d''emprunter du matériel pour quelqu''un d''autre</li>
                            <li>Le matériel doit être emprunté pour une sortie collective officielle du club déposée sur ce site internet, il n''est pas possible de louer du matériel pour un usage en dehors de ces sorties.</li>
                            <li>Vous pouvez venir chercher le matériel le jeudi précédent la sortie, et le restituer le mardi suivant, lors des permanences matériel de 18h30 à 20h.</li>
                            <li>Merci d''indiquer dans les notes additionnelles (lors de la validation de votre panier sur la plateforme) la sortie pour laquelle le matériel est emprunté, ainsi que le nom de l''encadrant de la sortie.</li>
                            <li>La réservation doit être acceptée puis payée, sans quoi matériel ne pourra pas être récupéré</li>
                            <li>Le matériel doit être rendu dans l''état où il a été emprunté ou à défaut, merci de signaler tout problème rencontré.</li>
                        </ul>
                    </div>
                </div>
            </div>
            <br>
            <p>
                Nous ne demandons pas de caution, mais en cas de perte ou détérioration du matériel, il vous sera demandé de régler les frais de réparation, ou la valeur de remplacement du matériel concerné.
            </p>
            <p>
                Merci.
            </p>', UNIX_TIMESTAMP(), '', 1, 1);
        SQL);
        $this->addSql(<<<SQL
            INSERT INTO `caf_content_html` (`code_content_html`, `lang_content_html`, `contenu_content_html`, `date_content_html`, `linkedtopage_content_html`, `current_content_html`, `vis_content_html`)
            VALUES ('materiel-access', 'fr', '<p>
                Pour accéder à cette plateforme de réservation, vous devez créer un compte sur notre outil de gestion.
                Une fois votre compte créé, vous pourrez :
            </p>

            <div class="p-4 mb-6 border-l-4 border-blue-500 bg-blue-50">
                <div class="flex">
                    <div class="ml-3">
                        <ul class="text-sm text-blue-700">
                            <li>Vous connecter sur la plateforme Loxya</li>
                            <li>Consulter le matériel disponible à vos dates</li>
                            <li>Faire une demande de réservation</li>
                        </ul>
                    </div>
                </div>
            </div>', UNIX_TIMESTAMP(), '', 1, 1);
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM caf_content_html WHERE code_content_html = \'materiel-explanation\'');
        $this->addSql('DELETE FROM caf_content_html WHERE code_content_html = \'materiel-access\'');
    }
}
