<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250523122955 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE caf_userright SET title_userright = 'Modifier un article (rédigé par un tiers) au sein d\'une commission' WHERE code_userright = 'article_edit_notmine'");
        $this->addSql("UPDATE caf_userright SET title_userright = 'Supprimer un article (rédigé par un tiers) au sein d\'une commission' WHERE code_userright = 'article_delete_notmine'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE caf_userright SET title_userright = 'Modifier un article (rédigé par un tiers)' WHERE code_userright = 'article_edit_notmine'");
        $this->addSql("UPDATE caf_userright SET title_userright = 'Supprimer un article (rédigé par un tiers)' WHERE code_userright = 'article_delete_notmine'");
    }
}
