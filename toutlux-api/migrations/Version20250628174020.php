<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250628174020 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE email_log (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, message_id INT DEFAULT NULL, to_email VARCHAR(255) NOT NULL, subject VARCHAR(255) NOT NULL, template VARCHAR(50) NOT NULL, template_data JSON NOT NULL, status VARCHAR(20) NOT NULL, error_message LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', sent_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_6FB4883A76ED395 (user_id), INDEX IDX_6FB4883537A1329 (message_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE message (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, subject VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, type VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL, is_read TINYINT(1) NOT NULL, email_sent TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', read_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_B6BD307FA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE email_log ADD CONSTRAINT FK_6FB4883A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE email_log ADD CONSTRAINT FK_6FB4883537A1329 FOREIGN KEY (message_id) REFERENCES message (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE message ADD CONSTRAINT FK_B6BD307FA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contact DROP FOREIGN KEY FK_4C62E63819268419
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contact DROP FOREIGN KEY FK_4C62E6386BB74515
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contact DROP FOREIGN KEY FK_4C62E6388EDA19B0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contact DROP FOREIGN KEY FK_4C62E638E92F8F78
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contact DROP FOREIGN KEY FK_4C62E638F624B39D
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contact_message DROP FOREIGN KEY FK_2C9211FE8EDA19B0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contact_message DROP FOREIGN KEY FK_2C9211FEE7A1254A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contact_message DROP FOREIGN KEY FK_2C9211FEF624B39D
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE validation_history DROP FOREIGN KEY FK_363F2D45642B8210
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE validation_history DROP FOREIGN KEY FK_363F2D45A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE contact
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE contact_message
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE notification
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE validation_history
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE contact (id INT AUTO_INCREMENT NOT NULL, sender_id INT NOT NULL, recipient_id INT NOT NULL, house_id INT NOT NULL, assigned_admin_id INT DEFAULT NULL, moderated_by_id INT DEFAULT NULL, message_type VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, subject VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, message LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, sender_phone VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, status VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, priority VARCHAR(10) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, is_read TINYINT(1) NOT NULL, read_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', last_message_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', metadata JSON NOT NULL, admin_notes VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, moderated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_4C62E638F624B39D (sender_id), INDEX IDX_4C62E638E92F8F78 (recipient_id), INDEX IDX_4C62E63819268419 (assigned_admin_id), INDEX IDX_4C62E6388EDA19B0 (moderated_by_id), INDEX idx_sender_unread (sender_id, is_read), INDEX idx_recipient_unread (recipient_id, is_read), INDEX idx_house (house_id), INDEX idx_status (status), INDEX idx_created_at (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE contact_message (id INT AUTO_INCREMENT NOT NULL, contact_id INT NOT NULL, sender_id INT NOT NULL, moderated_by_id INT DEFAULT NULL, message LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, type VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, attachments JSON NOT NULL, is_read TINYINT(1) NOT NULL, read_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', metadata JSON NOT NULL, is_moderated TINYINT(1) NOT NULL, moderated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', moderation_reason VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, client_ip VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, user_agent VARCHAR(500) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX idx_created_at (created_at), INDEX IDX_2C9211FEE7A1254A (contact_id), INDEX IDX_2C9211FE8EDA19B0 (moderated_by_id), INDEX idx_contact_date (contact_id, created_at), INDEX idx_sender (sender_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, message LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, type VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, priority VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, is_read TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', read_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', data JSON NOT NULL, action_url VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, icon VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, expires_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, action_label VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_BF5476CAA76ED395 (user_id), INDEX idx_user_unread (user_id, is_read), INDEX idx_created_at (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE validation_history (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, admin_id INT NOT NULL, document_type VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, approved TINYINT(1) NOT NULL, reason LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, metadata JSON DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', admin_ip VARCHAR(45) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_363F2D45A76ED395 (user_id), INDEX idx_user_created (user_id, created_at), INDEX idx_admin (admin_id), INDEX idx_type_approved (document_type, approved), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contact ADD CONSTRAINT FK_4C62E63819268419 FOREIGN KEY (assigned_admin_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contact ADD CONSTRAINT FK_4C62E6386BB74515 FOREIGN KEY (house_id) REFERENCES house (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contact ADD CONSTRAINT FK_4C62E6388EDA19B0 FOREIGN KEY (moderated_by_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contact ADD CONSTRAINT FK_4C62E638E92F8F78 FOREIGN KEY (recipient_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contact ADD CONSTRAINT FK_4C62E638F624B39D FOREIGN KEY (sender_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contact_message ADD CONSTRAINT FK_2C9211FE8EDA19B0 FOREIGN KEY (moderated_by_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contact_message ADD CONSTRAINT FK_2C9211FEE7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contact_message ADD CONSTRAINT FK_2C9211FEF624B39D FOREIGN KEY (sender_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE validation_history ADD CONSTRAINT FK_363F2D45642B8210 FOREIGN KEY (admin_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE validation_history ADD CONSTRAINT FK_363F2D45A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE email_log DROP FOREIGN KEY FK_6FB4883A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE email_log DROP FOREIGN KEY FK_6FB4883537A1329
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE email_log
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE message
        SQL);
    }
}
