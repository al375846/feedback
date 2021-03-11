<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210311110313 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE apprentice (id INT AUTO_INCREMENT NOT NULL, userdata_id INT NOT NULL, UNIQUE INDEX UNIQ_54C3A96DAB945D82 (userdata_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, INDEX IDX_64C19C1727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE expert (id INT AUTO_INCREMENT NOT NULL, userdata_id INT NOT NULL, UNIQUE INDEX UNIQ_4F1B9342AB945D82 (userdata_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE expert_categories (id INT AUTO_INCREMENT NOT NULL, expert_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_23282939C5568CE4 (expert_id), INDEX IDX_2328293912469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE feedback (id INT AUTO_INCREMENT NOT NULL, publication_id INT NOT NULL, expert_id INT NOT NULL, description LONGTEXT NOT NULL, video VARCHAR(255) NOT NULL, document VARCHAR(255) NOT NULL, images LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', INDEX IDX_D229445838B217A7 (publication_id), INDEX IDX_D2294458C5568CE4 (expert_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE incidence (id INT AUTO_INCREMENT NOT NULL, publication_id INT NOT NULL, type VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, INDEX IDX_1706041738B217A7 (publication_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE publication (id INT AUTO_INCREMENT NOT NULL, category_id INT NOT NULL, apprentice_id INT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, tags LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', video VARCHAR(255) DEFAULT NULL, document VARCHAR(255) DEFAULT NULL, images LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', INDEX IDX_AF3C677912469DE2 (category_id), INDEX IDX_AF3C67795BE609BD (apprentice_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE suggestion (id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, INDEX IDX_DD80F31B727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, lastname VARCHAR(255) NOT NULL, address VARCHAR(255) NOT NULL, phone VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE valoration (id INT AUTO_INCREMENT NOT NULL, feedback_id INT NOT NULL, expert_id INT NOT NULL, apprentice_id INT NOT NULL, grade INT NOT NULL, UNIQUE INDEX UNIQ_A0F38FD2D249A887 (feedback_id), INDEX IDX_A0F38FD2C5568CE4 (expert_id), INDEX IDX_A0F38FD25BE609BD (apprentice_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE apprentice ADD CONSTRAINT FK_54C3A96DAB945D82 FOREIGN KEY (userdata_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1727ACA70 FOREIGN KEY (parent_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE expert ADD CONSTRAINT FK_4F1B9342AB945D82 FOREIGN KEY (userdata_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE expert_categories ADD CONSTRAINT FK_23282939C5568CE4 FOREIGN KEY (expert_id) REFERENCES expert (id)');
        $this->addSql('ALTER TABLE expert_categories ADD CONSTRAINT FK_2328293912469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE feedback ADD CONSTRAINT FK_D229445838B217A7 FOREIGN KEY (publication_id) REFERENCES publication (id)');
        $this->addSql('ALTER TABLE feedback ADD CONSTRAINT FK_D2294458C5568CE4 FOREIGN KEY (expert_id) REFERENCES expert (id)');
        $this->addSql('ALTER TABLE incidence ADD CONSTRAINT FK_1706041738B217A7 FOREIGN KEY (publication_id) REFERENCES publication (id)');
        $this->addSql('ALTER TABLE publication ADD CONSTRAINT FK_AF3C677912469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE publication ADD CONSTRAINT FK_AF3C67795BE609BD FOREIGN KEY (apprentice_id) REFERENCES apprentice (id)');
        $this->addSql('ALTER TABLE suggestion ADD CONSTRAINT FK_DD80F31B727ACA70 FOREIGN KEY (parent_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE valoration ADD CONSTRAINT FK_A0F38FD2D249A887 FOREIGN KEY (feedback_id) REFERENCES feedback (id)');
        $this->addSql('ALTER TABLE valoration ADD CONSTRAINT FK_A0F38FD2C5568CE4 FOREIGN KEY (expert_id) REFERENCES expert (id)');
        $this->addSql('ALTER TABLE valoration ADD CONSTRAINT FK_A0F38FD25BE609BD FOREIGN KEY (apprentice_id) REFERENCES apprentice (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE publication DROP FOREIGN KEY FK_AF3C67795BE609BD');
        $this->addSql('ALTER TABLE valoration DROP FOREIGN KEY FK_A0F38FD25BE609BD');
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1727ACA70');
        $this->addSql('ALTER TABLE expert_categories DROP FOREIGN KEY FK_2328293912469DE2');
        $this->addSql('ALTER TABLE publication DROP FOREIGN KEY FK_AF3C677912469DE2');
        $this->addSql('ALTER TABLE suggestion DROP FOREIGN KEY FK_DD80F31B727ACA70');
        $this->addSql('ALTER TABLE expert_categories DROP FOREIGN KEY FK_23282939C5568CE4');
        $this->addSql('ALTER TABLE feedback DROP FOREIGN KEY FK_D2294458C5568CE4');
        $this->addSql('ALTER TABLE valoration DROP FOREIGN KEY FK_A0F38FD2C5568CE4');
        $this->addSql('ALTER TABLE valoration DROP FOREIGN KEY FK_A0F38FD2D249A887');
        $this->addSql('ALTER TABLE feedback DROP FOREIGN KEY FK_D229445838B217A7');
        $this->addSql('ALTER TABLE incidence DROP FOREIGN KEY FK_1706041738B217A7');
        $this->addSql('ALTER TABLE apprentice DROP FOREIGN KEY FK_54C3A96DAB945D82');
        $this->addSql('ALTER TABLE expert DROP FOREIGN KEY FK_4F1B9342AB945D82');
        $this->addSql('DROP TABLE apprentice');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE expert');
        $this->addSql('DROP TABLE expert_categories');
        $this->addSql('DROP TABLE feedback');
        $this->addSql('DROP TABLE incidence');
        $this->addSql('DROP TABLE publication');
        $this->addSql('DROP TABLE suggestion');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE valoration');
    }
}
