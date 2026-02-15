<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260215200109 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create refresh_tokens table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE refresh_tokens (
            id INT AUTO_INCREMENT NOT NULL, 
            user_id INT NOT NULL, 
            token VARCHAR(128) NOT NULL, 
            expires_at DATETIME NOT NULL, 
            created_at DATETIME NOT NULL, 
            UNIQUE INDEX UNIQ_9BACE7E15F37A13B (token), 
            INDEX IDX_9BACE7E1A76ED395 (user_id), 
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        $this->addSql('ALTER TABLE refresh_tokens ADD CONSTRAINT FK_9BACE7E1A76ED395 
            FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE refresh_tokens DROP FOREIGN KEY FK_9BACE7E1A76ED395');
        $this->addSql('DROP TABLE refresh_tokens');
    }
}