<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260216125811 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE job_assignments ADD rating INT DEFAULT NULL, ADD status VARCHAR(20) NOT NULL, CHANGE scheduled_at scheduled_date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE jobs ADD location VARCHAR(255) NOT NULL, CHANGE description description LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE jobs DROP location, CHANGE description description LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE job_assignments DROP rating, DROP status, CHANGE scheduled_date scheduled_at DATETIME NOT NULL');
    }
}
