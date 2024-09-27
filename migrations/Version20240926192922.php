<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240926192922 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO product (name, price) VALUES ('Iphone', 100), ('Headphones', 20), ('Case', 10)");
        $this->addSql("INSERT INTO coupon (code, discount_value) VALUES ('P100', 100), ('P10', 10)");
    }

    public function down(Schema $schema): void
    {
        //TODO: add down if needed
    }
}
