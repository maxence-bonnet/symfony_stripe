<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220416160612 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3A40BC2D5');
        $this->addSql('DROP INDEX UNIQ_A3C664D3A40BC2D5 ON subscription');
        $this->addSql('ALTER TABLE subscription DROP schedule_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE subscription ADD schedule_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3A40BC2D5 FOREIGN KEY (schedule_id) REFERENCES user_subscription_schedule (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A3C664D3A40BC2D5 ON subscription (schedule_id)');
    }
}
