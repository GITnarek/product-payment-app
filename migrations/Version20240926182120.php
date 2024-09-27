<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240926182120 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE coupon (
            id INT AUTO_INCREMENT NOT NULL, 
            code VARCHAR(255) NOT NULL, 
            discount_value DOUBLE PRECISION NOT NULL, 
            PRIMARY KEY(id),
            UNIQUE KEY unique_code_descount_value (code, discount_value),
            UNIQUE KEY unique_code (code)
            ) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(55) NOT NULL,
            price DOUBLE PRECISION NOT NULL,
            PRIMARY KEY(id),
            UNIQUE KEY unique_name_price (name, price)
        ) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE coupon');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
