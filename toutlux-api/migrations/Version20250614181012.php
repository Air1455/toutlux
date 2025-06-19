<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250614181012 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, text LONGTEXT NOT NULL, type VARCHAR(50) NOT NULL, priority VARCHAR(20) NOT NULL, is_read TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', read_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', data JSON DEFAULT NULL, related_entity_type VARCHAR(255) DEFAULT NULL, related_entity_id INT DEFAULT NULL, action_url VARCHAR(500) DEFAULT NULL, icon VARCHAR(50) DEFAULT NULL, scheduled_for DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', is_sent TINYINT(1) NOT NULL, INDEX IDX_BF5476CAA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD profile_picture VARCHAR(255) DEFAULT NULL, ADD user_type VARCHAR(20) DEFAULT NULL, ADD created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', ADD is_email_verified TINYINT(1) NOT NULL, ADD is_phone_verified TINYINT(1) NOT NULL, ADD is_identity_verified TINYINT(1) NOT NULL, ADD profile_views INT NOT NULL, ADD phone_number VARCHAR(20) DEFAULT NULL, ADD updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', ADD status VARCHAR(20) NOT NULL, ADD country VARCHAR(255) DEFAULT NULL, ADD city VARCHAR(255) DEFAULT NULL, ADD address VARCHAR(500) DEFAULT NULL, ADD identity_card VARCHAR(255) DEFAULT NULL, ADD identity_card_type VARCHAR(20) DEFAULT NULL, ADD identity_card_expiry_date DATE DEFAULT NULL, ADD selfie_with_id VARCHAR(255) DEFAULT NULL, ADD income_proof VARCHAR(255) DEFAULT NULL, ADD ownership_proof VARCHAR(255) DEFAULT NULL, ADD occupation VARCHAR(255) DEFAULT NULL, ADD income_source VARCHAR(30) DEFAULT NULL, ADD email_verified_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', ADD phone_verified_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', ADD identity_verified_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', ADD last_active_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', ADD terms_accepted TINYINT(1) DEFAULT NULL, ADD terms_accepted_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', ADD language VARCHAR(10) DEFAULT NULL, ADD last_login_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', ADD last_login_ip VARCHAR(45) DEFAULT NULL, DROP phone, DROP picture
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE notification
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `user` ADD phone VARCHAR(255) DEFAULT NULL, ADD picture VARCHAR(255) DEFAULT NULL, DROP profile_picture, DROP user_type, DROP created_at, DROP is_email_verified, DROP is_phone_verified, DROP is_identity_verified, DROP profile_views, DROP phone_number, DROP updated_at, DROP status, DROP country, DROP city, DROP address, DROP identity_card, DROP identity_card_type, DROP identity_card_expiry_date, DROP selfie_with_id, DROP income_proof, DROP ownership_proof, DROP occupation, DROP income_source, DROP email_verified_at, DROP phone_verified_at, DROP identity_verified_at, DROP last_active_at, DROP terms_accepted, DROP terms_accepted_at, DROP language, DROP last_login_at, DROP last_login_ip
        SQL);
    }
}
