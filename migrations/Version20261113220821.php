<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261113220821 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE admin_user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, roles JSON NOT NULL, UNIQUE INDEX UNIQ_AD8A54A9E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE block (id INT AUTO_INCREMENT NOT NULL, position INT NOT NULL, type VARCHAR(20) NOT NULL, content LONGTEXT DEFAULT NULL, caption VARCHAR(255) DEFAULT NULL, gallery_media_ids JSON NOT NULL, entry_id VARCHAR(100) NOT NULL, media_id VARCHAR(100) DEFAULT NULL, INDEX IDX_831B9722BA364942 (entry_id), INDEX IDX_831B9722EA9FDD75 (media_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE entry (id VARCHAR(100) NOT NULL, title VARCHAR(255) NOT NULL, date VARCHAR(10) DEFAULT NULL, trip_id VARCHAR(100) DEFAULT NULL, INDEX IDX_2B219D70A5BC2E0E (trip_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE entry_topic (entry_id VARCHAR(100) NOT NULL, topic_id VARCHAR(100) NOT NULL, INDEX IDX_195ABDBDBA364942 (entry_id), INDEX IDX_195ABDBD1F55203D (topic_id), PRIMARY KEY (entry_id, topic_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE keyword (id VARCHAR(100) NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE media (id VARCHAR(100) NOT NULL, kind VARCHAR(10) NOT NULL, file VARCHAR(255) NOT NULL, date VARCHAR(10) DEFAULT NULL, place VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE media_topic (media_id VARCHAR(100) NOT NULL, topic_id VARCHAR(100) NOT NULL, INDEX IDX_82591AAAEA9FDD75 (media_id), INDEX IDX_82591AAA1F55203D (topic_id), PRIMARY KEY (media_id, topic_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE media_keyword (media_id VARCHAR(100) NOT NULL, keyword_id VARCHAR(100) NOT NULL, INDEX IDX_3B44F772EA9FDD75 (media_id), INDEX IDX_3B44F772115D4552 (keyword_id), PRIMARY KEY (media_id, keyword_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE message (id VARCHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE topic (id VARCHAR(100) NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE trip (id VARCHAR(100) NOT NULL, title VARCHAR(255) NOT NULL, start_date VARCHAR(10) DEFAULT NULL, end_date VARCHAR(10) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE block ADD CONSTRAINT FK_831B9722BA364942 FOREIGN KEY (entry_id) REFERENCES entry (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE block ADD CONSTRAINT FK_831B9722EA9FDD75 FOREIGN KEY (media_id) REFERENCES media (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE entry ADD CONSTRAINT FK_2B219D70A5BC2E0E FOREIGN KEY (trip_id) REFERENCES trip (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE entry_topic ADD CONSTRAINT FK_195ABDBDBA364942 FOREIGN KEY (entry_id) REFERENCES entry (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE entry_topic ADD CONSTRAINT FK_195ABDBD1F55203D FOREIGN KEY (topic_id) REFERENCES topic (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE media_topic ADD CONSTRAINT FK_82591AAAEA9FDD75 FOREIGN KEY (media_id) REFERENCES media (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE media_topic ADD CONSTRAINT FK_82591AAA1F55203D FOREIGN KEY (topic_id) REFERENCES topic (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE media_keyword ADD CONSTRAINT FK_3B44F772EA9FDD75 FOREIGN KEY (media_id) REFERENCES media (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE media_keyword ADD CONSTRAINT FK_3B44F772115D4552 FOREIGN KEY (keyword_id) REFERENCES keyword (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE block DROP FOREIGN KEY FK_831B9722BA364942');
        $this->addSql('ALTER TABLE block DROP FOREIGN KEY FK_831B9722EA9FDD75');
        $this->addSql('ALTER TABLE entry DROP FOREIGN KEY FK_2B219D70A5BC2E0E');
        $this->addSql('ALTER TABLE entry_topic DROP FOREIGN KEY FK_195ABDBDBA364942');
        $this->addSql('ALTER TABLE entry_topic DROP FOREIGN KEY FK_195ABDBD1F55203D');
        $this->addSql('ALTER TABLE media_topic DROP FOREIGN KEY FK_82591AAAEA9FDD75');
        $this->addSql('ALTER TABLE media_topic DROP FOREIGN KEY FK_82591AAA1F55203D');
        $this->addSql('ALTER TABLE media_keyword DROP FOREIGN KEY FK_3B44F772EA9FDD75');
        $this->addSql('ALTER TABLE media_keyword DROP FOREIGN KEY FK_3B44F772115D4552');
        $this->addSql('DROP TABLE admin_user');
        $this->addSql('DROP TABLE block');
        $this->addSql('DROP TABLE entry');
        $this->addSql('DROP TABLE entry_topic');
        $this->addSql('DROP TABLE keyword');
        $this->addSql('DROP TABLE media');
        $this->addSql('DROP TABLE media_topic');
        $this->addSql('DROP TABLE media_keyword');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE topic');
        $this->addSql('DROP TABLE trip');
    }
}
