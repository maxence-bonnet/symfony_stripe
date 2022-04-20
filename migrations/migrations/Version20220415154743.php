<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220415154743 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE price DROP FOREIGN KEY FK_CAC822D93E8AC0D2');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D33E8AC0D2');
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, stripe_product_id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('DROP TABLE stripe_product');
        $this->addSql('DROP INDEX IDX_CAC822D93E8AC0D2 ON price');
        $this->addSql('ALTER TABLE price CHANGE stripe_product_id product_id INT NOT NULL');
        $this->addSql('ALTER TABLE price ADD CONSTRAINT FK_CAC822D94584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('CREATE INDEX IDX_CAC822D94584665A ON price (product_id)');
        $this->addSql('DROP INDEX UNIQ_A3C664D33E8AC0D2 ON subscription');
        $this->addSql('ALTER TABLE subscription DROP name, DROP description, DROP duration, DROP duration_unit, CHANGE stripe_product_id product_id INT NOT NULL');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D34584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A3C664D34584665A ON subscription (product_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE price DROP FOREIGN KEY FK_CAC822D94584665A');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D34584665A');
        $this->addSql('CREATE TABLE stripe_product (id INT AUTO_INCREMENT NOT NULL, stripe_product_id VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP INDEX IDX_CAC822D94584665A ON price');
        $this->addSql('ALTER TABLE price CHANGE product_id stripe_product_id INT NOT NULL');
        $this->addSql('ALTER TABLE price ADD CONSTRAINT FK_CAC822D93E8AC0D2 FOREIGN KEY (stripe_product_id) REFERENCES stripe_product (id)');
        $this->addSql('CREATE INDEX IDX_CAC822D93E8AC0D2 ON price (stripe_product_id)');
        $this->addSql('DROP INDEX UNIQ_A3C664D34584665A ON subscription');
        $this->addSql('ALTER TABLE subscription ADD name VARCHAR(255) NOT NULL, ADD description LONGTEXT NOT NULL, ADD duration INT DEFAULT NULL, ADD duration_unit VARCHAR(10) DEFAULT NULL, CHANGE product_id stripe_product_id INT NOT NULL');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D33E8AC0D2 FOREIGN KEY (stripe_product_id) REFERENCES stripe_product (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A3C664D33E8AC0D2 ON subscription (stripe_product_id)');
    }
}
