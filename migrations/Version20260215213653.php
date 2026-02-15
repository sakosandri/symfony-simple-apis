<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add created_at and updated_at columns to products table
 */
final class Version20260215213653 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add created_at and updated_at columns to products table';
    }

    public function up(Schema $schema): void
    {
        // Add the columns
        $this->addSql('ALTER TABLE products ADD created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE products ADD updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }

    public function down(Schema $schema): void
    {
        // Remove the columns if we rollback
        $this->addSql('ALTER TABLE products DROP created_at');
        $this->addSql('ALTER TABLE products DROP updated_at');
    }
}