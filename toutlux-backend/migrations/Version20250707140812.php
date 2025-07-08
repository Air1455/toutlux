<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250707140812 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE document (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, sub_type VARCHAR(100) DEFAULT NULL, file_name VARCHAR(255) DEFAULT NULL, file_size INT DEFAULT NULL, file_path VARCHAR(255) DEFAULT NULL, mime_type VARCHAR(100) DEFAULT NULL, original_name VARCHAR(255) DEFAULT NULL, title VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, status VARCHAR(255) NOT NULL, validated_at DATETIME DEFAULT NULL, rejection_reason LONGTEXT DEFAULT NULL, admin_note LONGTEXT DEFAULT NULL, validation_notes LONGTEXT DEFAULT NULL, expires_at DATETIME DEFAULT NULL, document_number VARCHAR(100) DEFAULT NULL, issuing_authority VARCHAR(100) DEFAULT NULL, issue_date DATE DEFAULT NULL, is_encrypted TINYINT(1) NOT NULL, checksum VARCHAR(255) DEFAULT NULL, encryption_key VARCHAR(255) DEFAULT NULL, extracted_data JSON DEFAULT NULL, ocr_processed TINYINT(1) NOT NULL, audit_log JSON NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, last_accessed_at DATETIME DEFAULT NULL, user_id INT NOT NULL, validated_by_id INT DEFAULT NULL, INDEX IDX_D8698A76A76ED395 (user_id), INDEX IDX_D8698A76C69DE5E5 (validated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE media_object (id INT AUTO_INCREMENT NOT NULL, file_path VARCHAR(255) DEFAULT NULL, content_url VARCHAR(255) DEFAULT NULL, mime_type VARCHAR(255) DEFAULT NULL, size INT DEFAULT NULL, original_name VARCHAR(255) DEFAULT NULL, dimensions JSON DEFAULT NULL, created_at DATETIME NOT NULL, owner_id INT DEFAULT NULL, INDEX IDX_14D431327E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE message (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, subject VARCHAR(255) DEFAULT NULL, is_read TINYINT(1) NOT NULL, read_at DATETIME DEFAULT NULL, status VARCHAR(255) NOT NULL, admin_validated TINYINT(1) NOT NULL, needs_moderation TINYINT(1) NOT NULL, edited_by_moderator TINYINT(1) NOT NULL, validated_at DATETIME DEFAULT NULL, moderated_at DATETIME DEFAULT NULL, moderation_reason LONGTEXT DEFAULT NULL, admin_note LONGTEXT DEFAULT NULL, original_content LONGTEXT DEFAULT NULL, deleted_by_sender TINYINT(1) NOT NULL, deleted_by_recipient TINYINT(1) NOT NULL, archived_by_sender TINYINT(1) NOT NULL, archived_by_recipient TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, sender_id INT NOT NULL, recipient_id INT NOT NULL, property_id INT DEFAULT NULL, validated_by_id INT DEFAULT NULL, moderated_by_id INT DEFAULT NULL, parent_message_id INT DEFAULT NULL, INDEX IDX_B6BD307FF624B39D (sender_id), INDEX IDX_B6BD307FE92F8F78 (recipient_id), INDEX IDX_B6BD307F549213EC (property_id), INDEX IDX_B6BD307FC69DE5E5 (validated_by_id), INDEX IDX_B6BD307F8EDA19B0 (moderated_by_id), INDEX IDX_B6BD307F14399779 (parent_message_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, title VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, data JSON DEFAULT NULL, is_read TINYINT(1) NOT NULL, read_at DATETIME DEFAULT NULL, priority VARCHAR(20) DEFAULT NULL, icon VARCHAR(50) DEFAULT NULL, action_url VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_BF5476CAA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE property (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, price NUMERIC(10, 2) NOT NULL, type VARCHAR(20) NOT NULL, surface INT NOT NULL, rooms INT NOT NULL, bedrooms INT NOT NULL, bathrooms INT NOT NULL, address VARCHAR(255) NOT NULL, city VARCHAR(100) NOT NULL, postal_code VARCHAR(10) NOT NULL, latitude NUMERIC(10, 7) DEFAULT NULL, longitude NUMERIC(10, 7) DEFAULT NULL, features JSON DEFAULT NULL, available TINYINT(1) NOT NULL, verified TINYINT(1) NOT NULL, featured TINYINT(1) NOT NULL, meta_title VARCHAR(255) DEFAULT NULL, meta_description LONGTEXT DEFAULT NULL, view_count INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, owner_id INT NOT NULL, INDEX IDX_8BF21CDE7E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE property_image (id INT AUTO_INCREMENT NOT NULL, image_name VARCHAR(255) DEFAULT NULL, image_size INT DEFAULT NULL, alt VARCHAR(255) DEFAULT NULL, is_main TINYINT(1) NOT NULL, position INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, property_id INT NOT NULL, INDEX IDX_32EC552549213EC (property_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE refresh_tokens (refresh_token VARCHAR(128) NOT NULL, username VARCHAR(255) NOT NULL, valid DATETIME NOT NULL, id INT AUTO_INCREMENT NOT NULL, UNIQUE INDEX UNIQ_9BACE7E1C74F2195 (refresh_token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(255) DEFAULT NULL, last_name VARCHAR(255) DEFAULT NULL, phone VARCHAR(20) DEFAULT NULL, birth_date DATE DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, city VARCHAR(100) DEFAULT NULL, postal_code VARCHAR(10) DEFAULT NULL, country VARCHAR(2) DEFAULT NULL, bio LONGTEXT DEFAULT NULL, avatar VARCHAR(255) DEFAULT NULL, avatar_name VARCHAR(255) DEFAULT NULL, trust_score NUMERIC(3, 2) DEFAULT NULL, is_email_verified TINYINT(1) NOT NULL, verified_at DATETIME DEFAULT NULL, phone_verified TINYINT(1) NOT NULL, profile_completed TINYINT(1) NOT NULL, identity_verified TINYINT(1) NOT NULL, financial_verified TINYINT(1) NOT NULL, terms_accepted TINYINT(1) NOT NULL, terms_accepted_at DATETIME DEFAULT NULL, email_notifications_enabled TINYINT(1) NOT NULL, sms_notifications_enabled TINYINT(1) NOT NULL, google_id VARCHAR(255) DEFAULT NULL, google_data JSON DEFAULT NULL, active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, last_login_at DATETIME DEFAULT NULL, last_verification_email_sent_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A76A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A76C69DE5E5 FOREIGN KEY (validated_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE media_object ADD CONSTRAINT FK_14D431327E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FF624B39D FOREIGN KEY (sender_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FE92F8F78 FOREIGN KEY (recipient_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F549213EC FOREIGN KEY (property_id) REFERENCES property (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FC69DE5E5 FOREIGN KEY (validated_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F8EDA19B0 FOREIGN KEY (moderated_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F14399779 FOREIGN KEY (parent_message_id) REFERENCES message (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE property ADD CONSTRAINT FK_8BF21CDE7E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE property_image ADD CONSTRAINT FK_32EC552549213EC FOREIGN KEY (property_id) REFERENCES property (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A76A76ED395');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A76C69DE5E5');
        $this->addSql('ALTER TABLE media_object DROP FOREIGN KEY FK_14D431327E3C61F9');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FF624B39D');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FE92F8F78');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F549213EC');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FC69DE5E5');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F8EDA19B0');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F14399779');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395');
        $this->addSql('ALTER TABLE property DROP FOREIGN KEY FK_8BF21CDE7E3C61F9');
        $this->addSql('ALTER TABLE property_image DROP FOREIGN KEY FK_32EC552549213EC');
        $this->addSql('DROP TABLE document');
        $this->addSql('DROP TABLE media_object');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE property');
        $this->addSql('DROP TABLE property_image');
        $this->addSql('DROP TABLE refresh_tokens');
        $this->addSql('DROP TABLE `user`');
    }
}
