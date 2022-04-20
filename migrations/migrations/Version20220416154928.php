<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220416154928 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE customer (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, stripe_customer_id VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_81398E09A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE customer ADD CONSTRAINT FK_81398E09A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('DROP TABLE user_subscription');
        $this->addSql('ALTER TABLE product ADD purpose VARCHAR(20) NOT NULL, ADD active TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D34584665A');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3A40BC2D5');
        $this->addSql('DROP INDEX UNIQ_A3C664D34584665A ON subscription');
        $this->addSql('ALTER TABLE subscription ADD price_id INT DEFAULT NULL, ADD stripe_subscription_id VARCHAR(255) NOT NULL, ADD created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD next_invoice_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD ends_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE product_id customer_id INT NOT NULL');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3D614C7E7 FOREIGN KEY (price_id) REFERENCES price (id)');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D39395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3A40BC2D5 FOREIGN KEY (schedule_id) REFERENCES user_subscription_schedule (id)');
        $this->addSql('CREATE INDEX IDX_A3C664D3D614C7E7 ON subscription (price_id)');
        $this->addSql('CREATE INDEX IDX_A3C664D39395C3F3 ON subscription (customer_id)');
        $this->addSql('ALTER TABLE user DROP stripe_customer_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D39395C3F3');
        $this->addSql('CREATE TABLE user_subscription (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, subscription_id INT NOT NULL, schedule_id INT DEFAULT NULL, price_id INT DEFAULT NULL, stripe_subscription_id VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', next_invoice_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', status VARCHAR(30) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_230A18D19A1887DC (subscription_id), INDEX IDX_230A18D1D614C7E7 (price_id), UNIQUE INDEX UNIQ_230A18D1A40BC2D5 (schedule_id), INDEX IDX_230A18D1A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE user_subscription ADD CONSTRAINT FK_230A18D1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_subscription ADD CONSTRAINT FK_230A18D1A40BC2D5 FOREIGN KEY (schedule_id) REFERENCES user_subscription_schedule (id)');
        $this->addSql('ALTER TABLE user_subscription ADD CONSTRAINT FK_230A18D1D614C7E7 FOREIGN KEY (price_id) REFERENCES price (id)');
        $this->addSql('ALTER TABLE user_subscription ADD CONSTRAINT FK_230A18D19A1887DC FOREIGN KEY (subscription_id) REFERENCES subscription (id)');
        $this->addSql('DROP TABLE customer');
        $this->addSql('ALTER TABLE product DROP purpose, DROP active');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3D614C7E7');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3A40BC2D5');
        $this->addSql('DROP INDEX IDX_A3C664D3D614C7E7 ON subscription');
        $this->addSql('DROP INDEX IDX_A3C664D39395C3F3 ON subscription');
        $this->addSql('ALTER TABLE subscription DROP price_id, DROP stripe_subscription_id, DROP created_at, DROP next_invoice_at, DROP ends_at, CHANGE customer_id product_id INT NOT NULL');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D34584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3A40BC2D5 FOREIGN KEY (schedule_id) REFERENCES subscription_schedule (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A3C664D34584665A ON subscription (product_id)');
        $this->addSql('ALTER TABLE user ADD stripe_customer_id VARCHAR(255) DEFAULT NULL');
    }
}
