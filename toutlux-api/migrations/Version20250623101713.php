<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250623101713 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE `contact` (id INT AUTO_INCREMENT NOT NULL, sender_id INT NOT NULL, recipient_id INT NOT NULL, house_id INT NOT NULL, assigned_admin_id INT DEFAULT NULL, moderated_by_id INT DEFAULT NULL, message_type VARCHAR(20) NOT NULL, subject VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, sender_phone VARCHAR(20) DEFAULT NULL, status VARCHAR(20) NOT NULL, priority VARCHAR(10) NOT NULL, is_read TINYINT(1) NOT NULL, read_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', last_message_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', metadata JSON NOT NULL, admin_notes VARCHAR(255) DEFAULT NULL, moderated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_4C62E638F624B39D (sender_id), INDEX IDX_4C62E638E92F8F78 (recipient_id), INDEX IDX_4C62E63819268419 (assigned_admin_id), INDEX IDX_4C62E6388EDA19B0 (moderated_by_id), INDEX idx_sender_unread (sender_id, is_read), INDEX idx_recipient_unread (recipient_id, is_read), INDEX idx_house (house_id), INDEX idx_status (status), INDEX idx_created_at (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE `contact_message` (id INT AUTO_INCREMENT NOT NULL, contact_id INT NOT NULL, sender_id INT NOT NULL, moderated_by_id INT DEFAULT NULL, message LONGTEXT NOT NULL, type VARCHAR(20) NOT NULL, attachments JSON NOT NULL, is_read TINYINT(1) NOT NULL, read_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', metadata JSON NOT NULL, is_moderated TINYINT(1) NOT NULL, moderated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', moderation_reason VARCHAR(255) DEFAULT NULL, client_ip VARCHAR(50) DEFAULT NULL, user_agent VARCHAR(500) DEFAULT NULL, INDEX IDX_2C9211FEE7A1254A (contact_id), INDEX IDX_2C9211FE8EDA19B0 (moderated_by_id), INDEX idx_contact_date (contact_id, created_at), INDEX idx_sender (sender_id), INDEX idx_created_at (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `contact` ADD CONSTRAINT FK_4C62E638F624B39D FOREIGN KEY (sender_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `contact` ADD CONSTRAINT FK_4C62E638E92F8F78 FOREIGN KEY (recipient_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `contact` ADD CONSTRAINT FK_4C62E6386BB74515 FOREIGN KEY (house_id) REFERENCES house (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `contact` ADD CONSTRAINT FK_4C62E63819268419 FOREIGN KEY (assigned_admin_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `contact` ADD CONSTRAINT FK_4C62E6388EDA19B0 FOREIGN KEY (moderated_by_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `contact_message` ADD CONSTRAINT FK_2C9211FEE7A1254A FOREIGN KEY (contact_id) REFERENCES `contact` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `contact_message` ADD CONSTRAINT FK_2C9211FEF624B39D FOREIGN KEY (sender_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `contact_message` ADD CONSTRAINT FK_2C9211FE8EDA19B0 FOREIGN KEY (moderated_by_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE house ADD status VARCHAR(20) NOT NULL, ADD metadata JSON NOT NULL, ADD created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', ADD updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE `contact` DROP FOREIGN KEY FK_4C62E638F624B39D
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `contact` DROP FOREIGN KEY FK_4C62E638E92F8F78
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `contact` DROP FOREIGN KEY FK_4C62E6386BB74515
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `contact` DROP FOREIGN KEY FK_4C62E63819268419
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `contact` DROP FOREIGN KEY FK_4C62E6388EDA19B0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `contact_message` DROP FOREIGN KEY FK_2C9211FEE7A1254A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `contact_message` DROP FOREIGN KEY FK_2C9211FEF624B39D
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `contact_message` DROP FOREIGN KEY FK_2C9211FE8EDA19B0
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE `contact`
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE `contact_message`
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE house DROP status, DROP metadata, DROP created_at, DROP updated_at
        SQL);
    }
}
