<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240716070744 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du contenu textuel "logo-img-name"';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            INSERT INTO `caf_content_inline` (`groupe_content_inline`, `code_content_inline`, `lang_content_inline`, `contenu_content_inline`, `date_content_inline`, `linkedtopage_content_inline`) VALUES
            (1, "logo-img-name", "fr", "logo.png", 1721113932, "0")
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM `caf_content_inline` where `code_content_inline` like "%logo-img-name%"');
    }
}
