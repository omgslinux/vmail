<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200101000002 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE autoreply (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, message LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, startdate DATETIME NOT NULL, enddate DATETIME NOT NULL, is_active TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_93AE66F7A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');

        $this->addSql('CREATE TABLE autoreply_cache (id INT AUTO_INCREMENT NOT NULL, reply_id INT DEFAULT NULL, sender VARCHAR(64) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, recipient VARCHAR(64) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, datesent DATETIME NOT NULL, INDEX IDX_877B44A88A0E4E7F (reply_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');

        $this->addSql('CREATE TABLE alias (id INT AUTO_INCREMENT NOT NULL, aliasname_id INT DEFAULT NULL, addressname_id INT DEFAULT NULL, is_active TINYINT(1) NOT NULL, INDEX IDX_E16C6B94B37DFEEA (addressname_id), INDEX IDX_E16C6B943AA28D71 (aliasname_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');

        $this->addSql('CREATE TABLE server_certificate (id INT AUTO_INCREMENT NOT NULL, domain_id INT NOT NULL, description VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, certdata JSON NOT NULL COMMENT \'(DC2Type:json)\', INDEX IDX_69FC2391115F0EE5 (domain_id), UNIQUE INDEX cert_description_unique (domain_id, description), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ')
        ;
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

        $this->addSql('DROP TABLE server_certificate');
        $this->addSql('DROP TABLE alias');
        $this->addSql('DROP TABLE autoreply_cache');
        $this->addSql('DROP TABLE autoreply');
    }
}
