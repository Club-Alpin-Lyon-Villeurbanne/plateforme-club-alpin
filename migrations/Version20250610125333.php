<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250610125333 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE caf_comment CHANGE cont_comment cont_comment TEXT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE caf_content_inline CHANGE contenu_content_inline contenu_content_inline TEXT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE caf_evt CHANGE tarif_detail tarif_detail LONGTEXT DEFAULT NULL, CHANGE denivele_evt denivele_evt LONGTEXT DEFAULT NULL, CHANGE distance_evt distance_evt LONGTEXT DEFAULT NULL, CHANGE matos_evt matos_evt LONGTEXT DEFAULT NULL, CHANGE itineraire itineraire LONGTEXT DEFAULT NULL, CHANGE description_evt description_evt LONGTEXT NOT NULL, CHANGE details_caches_evt details_caches_evt LONGTEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE caf_groupe CHANGE description description TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE caf_lieu CHANGE description description TEXT DEFAULT NULL, CHANGE ign ign TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE caf_message CHANGE cont_message cont_message TEXT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE caf_user_niveau CHANGE commentaire commentaire TEXT DEFAULT NULL
        SQL);
        if (!\in_array($_ENV['APP_ENV'], ['test'], true)) {
            $this->addSql(<<<'SQL'
                ALTER TABLE sessions CHANGE sess_data sess_data LONGBLOB NOT NULL
            SQL);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE caf_comment CHANGE cont_comment cont_comment MEDIUMTEXT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE caf_content_inline CHANGE contenu_content_inline contenu_content_inline MEDIUMTEXT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE caf_evt CHANGE tarif_detail tarif_detail MEDIUMTEXT DEFAULT NULL, CHANGE denivele_evt denivele_evt TEXT DEFAULT NULL, CHANGE distance_evt distance_evt TEXT DEFAULT NULL, CHANGE matos_evt matos_evt MEDIUMTEXT DEFAULT NULL, CHANGE itineraire itineraire MEDIUMTEXT DEFAULT NULL, CHANGE description_evt description_evt MEDIUMTEXT NOT NULL, CHANGE details_caches_evt details_caches_evt TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE caf_groupe CHANGE description description MEDIUMTEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE caf_lieu CHANGE description description MEDIUMTEXT DEFAULT NULL, CHANGE ign ign MEDIUMTEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE caf_message CHANGE cont_message cont_message MEDIUMTEXT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE caf_user_niveau CHANGE commentaire commentaire MEDIUMTEXT DEFAULT NULL
        SQL);
        if (!\in_array($_ENV['APP_ENV'], ['test'], true)) {
            $this->addSql(<<<'SQL'
                ALTER TABLE sessions CHANGE sess_data sess_data BLOB NOT NULL
            SQL);
        }
    }
}
