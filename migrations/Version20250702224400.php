<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add deleted_at column to users table for soft delete functionality
 */
final class Version20250702224400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add deleted_at column to users table for soft delete functionality';
    }

    public function up(Schema $schema): void
    {
        // Use platform-specific SQL for better compatibility
        $platform = $this->connection->getDatabasePlatform();
        
        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
            // Add deleted_at column as nullable timestamp
            $this->addSql('ALTER TABLE users ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL');
        } else {
            // SQLite compatible version
            $this->addSql('ALTER TABLE users ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL');
        }
        
        // Create index for better performance on deleted_at queries
        $this->addSql('CREATE INDEX IDX_users_deleted_at ON users (deleted_at)');
        
        // Create composite index for status and deleted_at for efficient filtering
        $this->addSql('CREATE INDEX IDX_users_status_deleted_at ON users (status, deleted_at)');
    }

    public function down(Schema $schema): void
    {
        // Remove indexes first
        $this->addSql('DROP INDEX IDX_users_status_deleted_at');
        $this->addSql('DROP INDEX IDX_users_deleted_at');
        
        // Remove deleted_at column
        $this->addSql('ALTER TABLE users DROP COLUMN deleted_at');
    }
}