<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241209125900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $backendUrl = $_ENV['BACKEND_URL'] ?? '';
        $env = $_ENV['APP_ENV'] ?? 'prod';
        $path = 'dev' === $env ? '/img/logo.png' : 'ftp/images/logo.png';
        $logoUrl = $backendUrl . $path;

        $this->addSql('
            INSERT INTO `caf_content_inline` (`groupe_content_inline`, `code_content_inline`, `lang_content_inline`, `contenu_content_inline`, `date_content_inline`, `linkedtopage_content_inline`) VALUES
            (1, "logo-img-src", "fr", :logoUrl, 1721113932, "0")
        ', ['logoUrl' => $logoUrl]);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM `caf_content_inline` where `code_content_inline` = "logo-img-src"');
    }
}
