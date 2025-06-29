<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250629200501 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD first_name VARCHAR(255) DEFAULT NULL, ADD last_name VARCHAR(255) DEFAULT NULL, ADD phone_number VARCHAR(20) DEFAULT NULL, ADD phone_number_indicatif VARCHAR(5) DEFAULT NULL, ADD profile_picture VARCHAR(255) DEFAULT NULL, ADD user_type VARCHAR(20) DEFAULT NULL, ADD occupation VARCHAR(255) DEFAULT NULL, ADD income_source VARCHAR(30) DEFAULT NULL, ADD identity_card_type VARCHAR(20) DEFAULT NULL, ADD identity_card VARCHAR(255) DEFAULT NULL, ADD selfie_with_id VARCHAR(255) DEFAULT NULL, ADD income_proof VARCHAR(255) DEFAULT NULL, ADD ownership_proof VARCHAR(255) DEFAULT NULL, ADD is_email_verified TINYINT(1) NOT NULL, ADD is_phone_verified TINYINT(1) NOT NULL, ADD is_identity_verified TINYINT(1) NOT NULL, ADD is_financial_docs_verified TINYINT(1) NOT NULL, ADD email_verified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD phone_verified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD identity_verified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD financial_docs_verified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD email_confirmation_token VARCHAR(255) DEFAULT NULL, ADD email_confirmation_token_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD email_verification_attempts INT DEFAULT NULL, ADD last_email_verification_request_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD terms_accepted TINYINT(1) NOT NULL, ADD privacy_accepted TINYINT(1) NOT NULL, ADD marketing_accepted TINYINT(1) NOT NULL, ADD terms_accepted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD privacy_accepted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD marketing_accepted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD status VARCHAR(20) NOT NULL, ADD last_active_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD language VARCHAR(10) DEFAULT NULL, ADD google_id VARCHAR(255) DEFAULT NULL, ADD profile_views INT NOT NULL, ADD metadata JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `user` DROP first_name, DROP last_name, DROP phone_number, DROP phone_number_indicatif, DROP profile_picture, DROP user_type, DROP occupation, DROP income_source, DROP identity_card_type, DROP identity_card, DROP selfie_with_id, DROP income_proof, DROP ownership_proof, DROP is_email_verified, DROP is_phone_verified, DROP is_identity_verified, DROP is_financial_docs_verified, DROP email_verified_at, DROP phone_verified_at, DROP identity_verified_at, DROP financial_docs_verified_at, DROP email_confirmation_token, DROP email_confirmation_token_expires_at, DROP email_verification_attempts, DROP last_email_verification_request_at, DROP terms_accepted, DROP privacy_accepted, DROP marketing_accepted, DROP terms_accepted_at, DROP privacy_accepted_at, DROP marketing_accepted_at, DROP created_at, DROP updated_at, DROP status, DROP last_active_at, DROP language, DROP google_id, DROP profile_views, DROP metadata');
    }
}
