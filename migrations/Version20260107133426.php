<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260107133426 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds new indexes to content_html and content_inline tables to improve search performance.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX code_content_html ON caf_content_html (code_content_html)');
        $this->addSql('CREATE INDEX code_content_inline ON caf_content_inline (code_content_inline)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX code_content_html ON caf_content_html');
        $this->addSql('DROP INDEX code_content_inline ON caf_content_inline');
    }
}
