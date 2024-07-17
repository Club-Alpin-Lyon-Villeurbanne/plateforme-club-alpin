<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240717142903 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Change charset to utf8mb4, repair and optimize tables';
    }

    public function up(Schema $schema) : void
    {
        // Tables to be modified
        $tables = [
            'caf_article',
            'caf_bus',
            'caf_bus_lieu_destination',
            'caf_chron_launch',
            'caf_chron_operation',
            'caf_comment',
            'caf_commission',
            'caf_content_html',
            'caf_content_inline',
            'caf_content_inline_group',
            'caf_destination',
            'caf_evt',
            'caf_evt_destination',
            'caf_evt_join',
            'caf_galerie',
            'caf_groupe',
            'caf_img',
            'caf_lieu',
            'caf_log_admin',
            'caf_message',
            'caf_page',
            'caf_partenaires',
            'caf_token',
            'caf_user',
            'caf_user_attr',
            'caf_user_mailchange',
            'caf_user_mdpchange',
            'caf_user_niveau',
            'caf_userright',
            'caf_usertype',
            'caf_usertype_attr',
            'expense',
            'expense_field',
            'expense_field_type',
            'expense_group',
            'expense_report',
            'expense_type',
            'expense_type_expense_field_type',
        ];

        // Execute ALTER, REPAIR, and OPTIMIZE commands
        foreach ($tables as $table) {
            $this->addSql("ALTER TABLE `$table` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            $this->addSql("OPTIMIZE TABLE `$table`;");
        }
    }

   

    public function down(Schema $schema): void
    {
    }
}
