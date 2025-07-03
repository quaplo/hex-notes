<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241201000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create event_store table for Event Sourcing';
    }

    public function up(Schema $schema): void
    {
        // Use platform-specific SQL for better compatibility
        $platform = $this->connection->getDatabasePlatform();
        
        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
            $this->addSql('CREATE TABLE event_store (
                id SERIAL PRIMARY KEY,
                aggregate_id VARCHAR(36) NOT NULL,
                event_type VARCHAR(255) NOT NULL,
                event_data JSONB NOT NULL,
                version INTEGER NOT NULL,
                occurred_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
            )');
        } else {
            // SQLite compatible version
            $this->addSql('CREATE TABLE event_store (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                aggregate_id VARCHAR(36) NOT NULL,
                event_type VARCHAR(255) NOT NULL,
                event_data TEXT NOT NULL,
                version INTEGER NOT NULL,
                occurred_at DATETIME NOT NULL
            )');
        }
        
        $this->addSql('CREATE INDEX idx_aggregate_version ON event_store (aggregate_id, version)');
        $this->addSql('CREATE INDEX idx_occurred_at ON event_store (occurred_at)');
        $this->addSql('CREATE UNIQUE INDEX idx_aggregate_version_unique ON event_store (aggregate_id, version)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_aggregate_version_unique');
        $this->addSql('DROP INDEX idx_occurred_at');
        $this->addSql('DROP INDEX idx_aggregate_version');
        $this->addSql('DROP TABLE event_store');
    }
} 