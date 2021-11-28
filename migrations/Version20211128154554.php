<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211128154554 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX unique_content ON caf_content_html (code_content_html, current_content_html)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX unique_content ON caf_content_html');
    }
}
