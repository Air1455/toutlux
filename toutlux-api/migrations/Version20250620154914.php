<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250620154914 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD email_confirmation_token VARCHAR(255) DEFAULT NULL, ADD email_confirmation_token_expires_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', ADD email_verification_attempts INT DEFAULT NULL, ADD last_email_verification_request_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE `user` DROP email_confirmation_token, DROP email_confirmation_token_expires_at, DROP email_verification_attempts, DROP last_email_verification_request_at
        SQL);
    }
}
