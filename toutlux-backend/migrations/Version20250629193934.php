<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250629193934 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE house (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, first_image VARCHAR(255) DEFAULT NULL, other_images JSON DEFAULT NULL, price INT NOT NULL, bedrooms SMALLINT DEFAULT NULL, bathrooms SMALLINT DEFAULT NULL, address LONGTEXT NOT NULL, city VARCHAR(255) NOT NULL, country VARCHAR(255) NOT NULL, short_description VARCHAR(100) NOT NULL, long_description LONGTEXT DEFAULT NULL, location JSON NOT NULL, garages SMALLINT DEFAULT NULL, swimming_pools INT DEFAULT NULL, floors SMALLINT DEFAULT NULL, surface VARCHAR(255) DEFAULT NULL, type VARCHAR(255) NOT NULL, year_of_construction SMALLINT DEFAULT NULL, is_for_rent TINYINT(1) NOT NULL, currency VARCHAR(3) NOT NULL, status VARCHAR(20) NOT NULL, metadata JSON NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_67D5399DA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE house ADD CONSTRAINT FK_67D5399DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE house DROP FOREIGN KEY FK_67D5399DA76ED395');
        $this->addSql('DROP TABLE house');
    }
}
