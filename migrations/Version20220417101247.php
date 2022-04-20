<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220417101247 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE test_clock (id INT AUTO_INCREMENT NOT NULL, stripe_test_clock_id VARCHAR(255) NOT NULL, name VARCHAR(255) DEFAULT NULL, frozen_time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE customer ADD test_clock_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE customer ADD CONSTRAINT FK_81398E09B02489C4 FOREIGN KEY (test_clock_id) REFERENCES test_clock (id)');
        $this->addSql('CREATE INDEX IDX_81398E09B02489C4 ON customer (test_clock_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer DROP FOREIGN KEY FK_81398E09B02489C4');
        $this->addSql('DROP TABLE test_clock');
        $this->addSql('DROP INDEX IDX_81398E09B02489C4 ON customer');
        $this->addSql('ALTER TABLE customer DROP test_clock_id');
    }
}
