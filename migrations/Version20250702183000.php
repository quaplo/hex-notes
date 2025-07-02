<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add status column to users table for Rich Domain Model implementation
 */
final class Version20250702183000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add status column to users table for Rich Domain Model implementation';
    }

    public function up(Schema $schema): void
    {
        // Add status column with default value 'active'
        $this->addSql('ALTER TABLE users ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT \'active\'');
        
        // Create index for better performance on status queries
        $this->addSql('CREATE INDEX IDX_users_status ON users (status)');
    }

    public function down(Schema $schema): void
    {
        // Remove index first
        $this->addSql('DROP INDEX IDX_users_status');
        
        // Remove status column
        $this->addSql('ALTER TABLE users DROP COLUMN status');
    }
}