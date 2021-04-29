<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210429080801 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE document_info_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE onesignal_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE document_info (id INT NOT NULL, name VARCHAR(255) NOT NULL, size INT NOT NULL, type VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE onesignal (id INT NOT NULL, onesignal VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE "user" ADD onesignalid VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD notificationsids TEXT DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN "user".notificationsids IS \'(DC2Type:array)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE document_info_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE onesignal_id_seq CASCADE');
        $this->addSql('DROP TABLE document_info');
        $this->addSql('DROP TABLE onesignal');
        $this->addSql('ALTER TABLE "user" DROP onesignalid');
        $this->addSql('ALTER TABLE "user" DROP notificationsids');
    }
}
