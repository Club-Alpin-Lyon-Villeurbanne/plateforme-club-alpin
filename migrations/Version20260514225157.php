<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260514225157 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Bilan carbone : ajoute cout_carbone_per_person et retire le mode 'avion' des sorties existantes";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt ADD cout_carbone_per_person DOUBLE PRECISION DEFAULT NULL');

        // Le mode "avion" est retiré : facteur d'émission erroné et méthode OSRM
        // (routage routier) inadaptée aux trajets aériens. On nettoie le mode et
        // le coût carbone qui étaient faux. On conserve nb_km (distance routière
        // neutre, exploitable pour audit ou recalcul ultérieur).
        $this->addSql("UPDATE caf_evt SET mode_transport = NULL, cout_carbone = NULL, cout_carbone_per_person = NULL WHERE mode_transport = 'avion'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_evt DROP cout_carbone_per_person');
        // Pas de rollback du nettoyage 'avion' : les données effacées étaient incorrectes.
    }
}
