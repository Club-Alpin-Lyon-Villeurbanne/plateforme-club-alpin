<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211121133517 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user ENGINE = InnoDB');
        $this->addSql('ALTER TABLE caf_userright ENGINE = InnoDB');
        $this->addSql('ALTER TABLE caf_user_attr ENGINE = InnoDB');
        $this->addSql('ALTER TABLE caf_content_html ENGINE = InnoDB');
        $this->addSql('ALTER TABLE caf_galerie ENGINE = InnoDB');
        $this->addSql('ALTER TABLE caf_chron_launch ENGINE = InnoDB');
        $this->addSql('ALTER TABLE caf_content_inline ENGINE = InnoDB');
        $this->addSql('ALTER TABLE caf_chron_operation ENGINE = InnoDB');
        $this->addSql('ALTER TABLE caf_content_inline_group ENGINE = InnoDB');
        $this->addSql('ALTER TABLE caf_page ENGINE = InnoDB');
        $this->addSql('ALTER TABLE caf_usertype ENGINE = InnoDB');
        $this->addSql('ALTER TABLE caf_message ENGINE = InnoDB');
        $this->addSql('ALTER TABLE caf_token ENGINE = InnoDB');
        $this->addSql('ALTER TABLE caf_evt_join ENGINE = InnoDB');
        $this->addSql('ALTER TABLE caf_img ENGINE = InnoDB');
        $this->addSql('ALTER TABLE caf_usertype_attr ENGINE = InnoDB');
        $this->addSql('ALTER TABLE caf_evt ENGINE = InnoDB');
        $this->addSql('ALTER TABLE caf_log_admin ENGINE = InnoDB');
        $this->addSql('ALTER TABLE caf_commission ENGINE = InnoDB');
        $this->addSql('ALTER TABLE caf_user_mdpchange ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caf_user ENGINE = MyISAM');
        $this->addSql('ALTER TABLE caf_userright ENGINE = MyISAM');
        $this->addSql('ALTER TABLE caf_user_attr ENGINE = MyISAM');
        $this->addSql('ALTER TABLE caf_content_html ENGINE = MyISAM');
        $this->addSql('ALTER TABLE caf_galerie ENGINE = MyISAM');
        $this->addSql('ALTER TABLE caf_chron_launch ENGINE = MyISAM');
        $this->addSql('ALTER TABLE caf_content_inline ENGINE = MyISAM');
        $this->addSql('ALTER TABLE caf_chron_operation ENGINE = MyISAM');
        $this->addSql('ALTER TABLE caf_content_inline_group ENGINE = MyISAM');
        $this->addSql('ALTER TABLE caf_page ENGINE = MyISAM');
        $this->addSql('ALTER TABLE caf_usertype ENGINE = MyISAM');
        $this->addSql('ALTER TABLE caf_message ENGINE = MyISAM');
        $this->addSql('ALTER TABLE caf_token ENGINE = MyISAM');
        $this->addSql('ALTER TABLE caf_evt_join ENGINE = MyISAM');
        $this->addSql('ALTER TABLE caf_img ENGINE = MyISAM');
        $this->addSql('ALTER TABLE caf_usertype_attr ENGINE = MyISAM');
        $this->addSql('ALTER TABLE caf_evt ENGINE = MyISAM');
        $this->addSql('ALTER TABLE caf_log_admin ENGINE = MyISAM');
        $this->addSql('ALTER TABLE caf_commission ENGINE = MyISAM');
        $this->addSql('ALTER TABLE caf_user_mdpchange ENGINE = MyISAM');
    }
}
