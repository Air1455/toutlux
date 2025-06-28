<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250621083827 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE `validation_history` (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, admin_id INT NOT NULL, document_type VARCHAR(20) NOT NULL, approved TINYINT(1) NOT NULL, reason LONGTEXT DEFAULT NULL, metadata JSON DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', admin_ip VARCHAR(45) DEFAULT NULL, INDEX IDX_363F2D45A76ED395 (user_id), INDEX idx_user_created (user_id, created_at), INDEX idx_admin (admin_id), INDEX idx_type_approved (document_type, approved), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `validation_history` ADD CONSTRAINT FK_363F2D45A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `validation_history` ADD CONSTRAINT FK_363F2D45642B8210 FOREIGN KEY (admin_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification ADD title VARCHAR(255) NOT NULL, ADD action_label VARCHAR(50) DEFAULT NULL, DROP related_entity_type, DROP related_entity_id, DROP is_sent, CHANGE data data JSON NOT NULL, CHANGE action_url action_url VARCHAR(255) DEFAULT NULL, CHANGE text message LONGTEXT NOT NULL, CHANGE scheduled_for expires_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_user_unread ON notification (user_id, is_read)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_created_at ON notification (created_at)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE `validation_history` DROP FOREIGN KEY FK_363F2D45A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `validation_history` DROP FOREIGN KEY FK_363F2D45642B8210
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE `validation_history`
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `notification` DROP FOREIGN KEY FK_BF5476CAA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_user_unread ON `notification`
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_created_at ON `notification`
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `notification` ADD related_entity_type VARCHAR(255) DEFAULT NULL, ADD related_entity_id INT DEFAULT NULL, ADD is_sent TINYINT(1) NOT NULL, DROP title, DROP action_label, CHANGE data data JSON DEFAULT NULL, CHANGE action_url action_url VARCHAR(500) DEFAULT NULL, CHANGE message text LONGTEXT NOT NULL, CHANGE expires_at scheduled_for DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `notification` ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE
        SQL);
    }
}
