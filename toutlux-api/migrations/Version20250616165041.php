<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250616165041 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD privacy_accepted TINYINT(1) NOT NULL, ADD privacy_accepted_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', ADD marketing_accepted TINYINT(1) NOT NULL, ADD marketing_accepted_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE first_name first_name VARCHAR(255) DEFAULT NULL, CHANGE last_name last_name VARCHAR(255) DEFAULT NULL, CHANGE terms_accepted terms_accepted TINYINT(1) NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE `user` DROP privacy_accepted, DROP privacy_accepted_at, DROP marketing_accepted, DROP marketing_accepted_at, CHANGE first_name first_name VARCHAR(255) NOT NULL, CHANGE last_name last_name VARCHAR(255) NOT NULL, CHANGE terms_accepted terms_accepted TINYINT(1) DEFAULT NULL
        SQL);
    }
}
