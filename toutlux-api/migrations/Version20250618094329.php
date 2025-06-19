<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250618094329 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP profile_views, DROP country, DROP city, DROP address, DROP identity_card_expiry_date, DROP last_login_at, DROP last_login_ip
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE `user` ADD profile_views INT NOT NULL, ADD country VARCHAR(255) DEFAULT NULL, ADD city VARCHAR(255) DEFAULT NULL, ADD address VARCHAR(500) DEFAULT NULL, ADD identity_card_expiry_date DATE DEFAULT NULL, ADD last_login_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', ADD last_login_ip VARCHAR(45) DEFAULT NULL
        SQL);
    }
}
