<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260105093744 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add IMAP and SMTP capabilities for users';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE alias CHANGE is_active is_active TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE autoreply CHANGE is_active is_active TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE domain CHANGE is_active is_active TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE domain RENAME INDEX name_unique TO UNIQ_A7A91E0B5E237E06');
        $this->addSql('ALTER TABLE user ADD imap TINYINT(1) DEFAULT NULL, ADD smtp TINYINT(1) DEFAULT NULL, CHANGE is_active is_active TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE autoreply CHANGE is_active is_active TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE alias CHANGE is_active is_active TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE domain CHANGE is_active is_active TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE domain RENAME INDEX uniq_a7a91e0b5e237e06 TO name_unique');
        $this->addSql('ALTER TABLE user DROP imap, DROP smtp, CHANGE is_active is_active TINYINT(1) NOT NULL');
    }
}
