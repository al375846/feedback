<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210320105620 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE apprentice_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE category_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE expert_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE expert_categories_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE feedback_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE incidence_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE publication_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE suggestion_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE "user_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE valoration_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE apprentice (id INT NOT NULL, userdata_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_54C3A96DAB945D82 ON apprentice (userdata_id)');
        $this->addSql('CREATE TABLE category (id INT NOT NULL, parent_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_64C19C1727ACA70 ON category (parent_id)');
        $this->addSql('CREATE TABLE expert (id INT NOT NULL, userdata_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4F1B9342AB945D82 ON expert (userdata_id)');
        $this->addSql('CREATE TABLE expert_categories (id INT NOT NULL, expert_id INT NOT NULL, category_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_23282939C5568CE4 ON expert_categories (expert_id)');
        $this->addSql('CREATE INDEX IDX_2328293912469DE2 ON expert_categories (category_id)');
        $this->addSql('CREATE TABLE feedback (id INT NOT NULL, publication_id INT NOT NULL, expert_id INT NOT NULL, description TEXT NOT NULL, video TEXT DEFAULT NULL, document TEXT DEFAULT NULL, images TEXT DEFAULT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D229445838B217A7 ON feedback (publication_id)');
        $this->addSql('CREATE INDEX IDX_D2294458C5568CE4 ON feedback (expert_id)');
        $this->addSql('COMMENT ON COLUMN feedback.video IS \'(DC2Type:array)\'');
        $this->addSql('COMMENT ON COLUMN feedback.document IS \'(DC2Type:array)\'');
        $this->addSql('COMMENT ON COLUMN feedback.images IS \'(DC2Type:array)\'');
        $this->addSql('CREATE TABLE incidence (id INT NOT NULL, publication_id INT NOT NULL, type VARCHAR(255) NOT NULL, description TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_1706041738B217A7 ON incidence (publication_id)');
        $this->addSql('CREATE TABLE publication (id INT NOT NULL, category_id INT NOT NULL, apprentice_id INT NOT NULL, title VARCHAR(255) NOT NULL, description TEXT NOT NULL, tags TEXT DEFAULT NULL, video TEXT DEFAULT NULL, document TEXT DEFAULT NULL, images TEXT DEFAULT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_AF3C677912469DE2 ON publication (category_id)');
        $this->addSql('CREATE INDEX IDX_AF3C67795BE609BD ON publication (apprentice_id)');
        $this->addSql('COMMENT ON COLUMN publication.tags IS \'(DC2Type:array)\'');
        $this->addSql('COMMENT ON COLUMN publication.video IS \'(DC2Type:array)\'');
        $this->addSql('COMMENT ON COLUMN publication.document IS \'(DC2Type:array)\'');
        $this->addSql('COMMENT ON COLUMN publication.images IS \'(DC2Type:array)\'');
        $this->addSql('CREATE TABLE suggestion (id INT NOT NULL, parent_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_DD80F31B727ACA70 ON suggestion (parent_id)');
        $this->addSql('CREATE TABLE "user" (id INT NOT NULL, username VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, lastname VARCHAR(255) NOT NULL, address VARCHAR(255) NOT NULL, phone VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677 ON "user" (username)');
        $this->addSql('CREATE TABLE valoration (id INT NOT NULL, feedback_id INT NOT NULL, expert_id INT NOT NULL, apprentice_id INT NOT NULL, grade INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A0F38FD2D249A887 ON valoration (feedback_id)');
        $this->addSql('CREATE INDEX IDX_A0F38FD2C5568CE4 ON valoration (expert_id)');
        $this->addSql('CREATE INDEX IDX_A0F38FD25BE609BD ON valoration (apprentice_id)');
        $this->addSql('ALTER TABLE apprentice ADD CONSTRAINT FK_54C3A96DAB945D82 FOREIGN KEY (userdata_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1727ACA70 FOREIGN KEY (parent_id) REFERENCES category (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE expert ADD CONSTRAINT FK_4F1B9342AB945D82 FOREIGN KEY (userdata_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE expert_categories ADD CONSTRAINT FK_23282939C5568CE4 FOREIGN KEY (expert_id) REFERENCES expert (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE expert_categories ADD CONSTRAINT FK_2328293912469DE2 FOREIGN KEY (category_id) REFERENCES category (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE feedback ADD CONSTRAINT FK_D229445838B217A7 FOREIGN KEY (publication_id) REFERENCES publication (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE feedback ADD CONSTRAINT FK_D2294458C5568CE4 FOREIGN KEY (expert_id) REFERENCES expert (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE incidence ADD CONSTRAINT FK_1706041738B217A7 FOREIGN KEY (publication_id) REFERENCES publication (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE publication ADD CONSTRAINT FK_AF3C677912469DE2 FOREIGN KEY (category_id) REFERENCES category (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE publication ADD CONSTRAINT FK_AF3C67795BE609BD FOREIGN KEY (apprentice_id) REFERENCES apprentice (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE suggestion ADD CONSTRAINT FK_DD80F31B727ACA70 FOREIGN KEY (parent_id) REFERENCES category (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE valoration ADD CONSTRAINT FK_A0F38FD2D249A887 FOREIGN KEY (feedback_id) REFERENCES feedback (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE valoration ADD CONSTRAINT FK_A0F38FD2C5568CE4 FOREIGN KEY (expert_id) REFERENCES expert (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE valoration ADD CONSTRAINT FK_A0F38FD25BE609BD FOREIGN KEY (apprentice_id) REFERENCES apprentice (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE publication DROP CONSTRAINT FK_AF3C67795BE609BD');
        $this->addSql('ALTER TABLE valoration DROP CONSTRAINT FK_A0F38FD25BE609BD');
        $this->addSql('ALTER TABLE category DROP CONSTRAINT FK_64C19C1727ACA70');
        $this->addSql('ALTER TABLE expert_categories DROP CONSTRAINT FK_2328293912469DE2');
        $this->addSql('ALTER TABLE publication DROP CONSTRAINT FK_AF3C677912469DE2');
        $this->addSql('ALTER TABLE suggestion DROP CONSTRAINT FK_DD80F31B727ACA70');
        $this->addSql('ALTER TABLE expert_categories DROP CONSTRAINT FK_23282939C5568CE4');
        $this->addSql('ALTER TABLE feedback DROP CONSTRAINT FK_D2294458C5568CE4');
        $this->addSql('ALTER TABLE valoration DROP CONSTRAINT FK_A0F38FD2C5568CE4');
        $this->addSql('ALTER TABLE valoration DROP CONSTRAINT FK_A0F38FD2D249A887');
        $this->addSql('ALTER TABLE feedback DROP CONSTRAINT FK_D229445838B217A7');
        $this->addSql('ALTER TABLE incidence DROP CONSTRAINT FK_1706041738B217A7');
        $this->addSql('ALTER TABLE apprentice DROP CONSTRAINT FK_54C3A96DAB945D82');
        $this->addSql('ALTER TABLE expert DROP CONSTRAINT FK_4F1B9342AB945D82');
        $this->addSql('DROP SEQUENCE apprentice_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE category_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE expert_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE expert_categories_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE feedback_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE incidence_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE publication_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE suggestion_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE "user_id_seq" CASCADE');
        $this->addSql('DROP SEQUENCE valoration_id_seq CASCADE');
        $this->addSql('DROP TABLE apprentice');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE expert');
        $this->addSql('DROP TABLE expert_categories');
        $this->addSql('DROP TABLE feedback');
        $this->addSql('DROP TABLE incidence');
        $this->addSql('DROP TABLE publication');
        $this->addSql('DROP TABLE suggestion');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE valoration');
    }
}
