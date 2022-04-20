<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220414185731 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE price (id INT AUTO_INCREMENT NOT NULL, stripe_product_id INT NOT NULL, stripe_price_id VARCHAR(255) NOT NULL, type VARCHAR(60) NOT NULL, price DOUBLE PRECISION NOT NULL, recurring_interval VARCHAR(10) DEFAULT NULL, recurring_count INT DEFAULT NULL, INDEX IDX_CAC822D93E8AC0D2 (stripe_product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE schedule_phase (id INT AUTO_INCREMENT NOT NULL, price_id INT NOT NULL, iterations INT NOT NULL, priority_order INT DEFAULT NULL, INDEX IDX_4DE41697D614C7E7 (price_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE schedule_phase_subscription_schedule (schedule_phase_id INT NOT NULL, subscription_schedule_id INT NOT NULL, INDEX IDX_25B94EE125E2F8BE (schedule_phase_id), INDEX IDX_25B94EE19911B242 (subscription_schedule_id), PRIMARY KEY(schedule_phase_id, subscription_schedule_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE stripe_product (id INT AUTO_INCREMENT NOT NULL, stripe_product_id VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE subscription (id INT AUTO_INCREMENT NOT NULL, stripe_product_id INT NOT NULL, schedule_id INT DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, duration INT NOT NULL, duration_unit VARCHAR(10) NOT NULL, status VARCHAR(30) NOT NULL, UNIQUE INDEX UNIQ_A3C664D33E8AC0D2 (stripe_product_id), UNIQUE INDEX UNIQ_A3C664D3A40BC2D5 (schedule_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE subscription_schedule (id INT AUTO_INCREMENT NOT NULL, end_behaviour VARCHAR(20) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, stripe_customer_id VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_subscription (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, subscription_id INT NOT NULL, schedule_id INT DEFAULT NULL, price_id INT DEFAULT NULL, stripe_subscription_id VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', next_invoice_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', status VARCHAR(30) NOT NULL, INDEX IDX_230A18D1A76ED395 (user_id), INDEX IDX_230A18D19A1887DC (subscription_id), UNIQUE INDEX UNIQ_230A18D1A40BC2D5 (schedule_id), INDEX IDX_230A18D1D614C7E7 (price_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_subscription_schedule (id INT AUTO_INCREMENT NOT NULL, stripe_subscription_schedule_id VARCHAR(255) NOT NULL, start_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', end_behaviour VARCHAR(20) NOT NULL, status VARCHAR(30) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE price ADD CONSTRAINT FK_CAC822D93E8AC0D2 FOREIGN KEY (stripe_product_id) REFERENCES stripe_product (id)');
        $this->addSql('ALTER TABLE schedule_phase ADD CONSTRAINT FK_4DE41697D614C7E7 FOREIGN KEY (price_id) REFERENCES price (id)');
        $this->addSql('ALTER TABLE schedule_phase_subscription_schedule ADD CONSTRAINT FK_25B94EE125E2F8BE FOREIGN KEY (schedule_phase_id) REFERENCES schedule_phase (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE schedule_phase_subscription_schedule ADD CONSTRAINT FK_25B94EE19911B242 FOREIGN KEY (subscription_schedule_id) REFERENCES subscription_schedule (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D33E8AC0D2 FOREIGN KEY (stripe_product_id) REFERENCES stripe_product (id)');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3A40BC2D5 FOREIGN KEY (schedule_id) REFERENCES subscription_schedule (id)');
        $this->addSql('ALTER TABLE user_subscription ADD CONSTRAINT FK_230A18D1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_subscription ADD CONSTRAINT FK_230A18D19A1887DC FOREIGN KEY (subscription_id) REFERENCES subscription (id)');
        $this->addSql('ALTER TABLE user_subscription ADD CONSTRAINT FK_230A18D1A40BC2D5 FOREIGN KEY (schedule_id) REFERENCES user_subscription_schedule (id)');
        $this->addSql('ALTER TABLE user_subscription ADD CONSTRAINT FK_230A18D1D614C7E7 FOREIGN KEY (price_id) REFERENCES price (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE schedule_phase DROP FOREIGN KEY FK_4DE41697D614C7E7');
        $this->addSql('ALTER TABLE user_subscription DROP FOREIGN KEY FK_230A18D1D614C7E7');
        $this->addSql('ALTER TABLE schedule_phase_subscription_schedule DROP FOREIGN KEY FK_25B94EE125E2F8BE');
        $this->addSql('ALTER TABLE price DROP FOREIGN KEY FK_CAC822D93E8AC0D2');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D33E8AC0D2');
        $this->addSql('ALTER TABLE user_subscription DROP FOREIGN KEY FK_230A18D19A1887DC');
        $this->addSql('ALTER TABLE schedule_phase_subscription_schedule DROP FOREIGN KEY FK_25B94EE19911B242');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3A40BC2D5');
        $this->addSql('ALTER TABLE user_subscription DROP FOREIGN KEY FK_230A18D1A76ED395');
        $this->addSql('ALTER TABLE user_subscription DROP FOREIGN KEY FK_230A18D1A40BC2D5');
        $this->addSql('DROP TABLE price');
        $this->addSql('DROP TABLE schedule_phase');
        $this->addSql('DROP TABLE schedule_phase_subscription_schedule');
        $this->addSql('DROP TABLE stripe_product');
        $this->addSql('DROP TABLE subscription');
        $this->addSql('DROP TABLE subscription_schedule');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_subscription');
        $this->addSql('DROP TABLE user_subscription_schedule');
    }
}
