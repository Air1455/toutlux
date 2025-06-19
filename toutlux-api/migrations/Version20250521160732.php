<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250521160732 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE house (id INT AUTO_INCREMENT NOT NULL, first_image VARCHAR(255) DEFAULT NULL, other_images JSON DEFAULT NULL, price INT NOT NULL, bedrooms SMALLINT DEFAULT NULL, bathrooms SMALLINT DEFAULT NULL, address LONGTEXT NOT NULL, city VARCHAR(255) NOT NULL, country VARCHAR(255) NOT NULL, short_description VARCHAR(50) NOT NULL, long_description LONGTEXT DEFAULT NULL, location JSON NOT NULL, garages SMALLINT DEFAULT NULL, swimming_pools INT DEFAULT NULL, floors SMALLINT DEFAULT NULL, surface VARCHAR(255) DEFAULT NULL, type VARCHAR(255) NOT NULL, year_of_construction SMALLINT DEFAULT NULL, is_for_rent TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP TABLE house
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE `user`
        SQL);
    }
}
