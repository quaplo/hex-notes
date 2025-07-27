<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Optimize event_store primary key to compound (aggregate_id, version) - Event Sourcing best practice
 */
final class Version20250727135000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace artificial INT id with compound primary key (aggregate_id, version) for Event Sourcing optimization';
    }

    public function up(Schema $schema): void
    {
        // PostgreSQL syntax: Drop existing primary key constraint by name
        $this->addSql('ALTER TABLE event_store DROP CONSTRAINT event_store_pkey');
        $this->addSql('ALTER TABLE event_store DROP COLUMN id');
        
        // Create compound primary key (aggregate_id, version)
        $this->addSql('ALTER TABLE event_store ADD CONSTRAINT event_store_pkey PRIMARY KEY (aggregate_id, version)');
        
        // Drop old indexes that are now redundant
        $this->addSql('DROP INDEX IF EXISTS idx_aggregate_type_id_version');
        
        // Create new optimized indexes without redundant aggregate_id
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_aggregate_type_occurred_at ON event_store (aggregate_type, occurred_at)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_occurred_at ON event_store (occurred_at)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_aggregate_type ON event_store (aggregate_type)');
    }

    public function down(Schema $schema): void
    {
        // PostgreSQL syntax: Drop compound primary key
        $this->addSql('ALTER TABLE event_store DROP CONSTRAINT event_store_pkey');
        
        // Add back SERIAL id column as primary key (PostgreSQL equivalent of AUTO_INCREMENT)
        $this->addSql('ALTER TABLE event_store ADD COLUMN id SERIAL PRIMARY KEY');
        
        // Drop compound-key optimized indexes
        $this->addSql('DROP INDEX IF EXISTS idx_aggregate_type');
        $this->addSql('DROP INDEX IF EXISTS idx_aggregate_type_occurred_at');
        $this->addSql('DROP INDEX IF EXISTS idx_occurred_at');
        
        // Recreate original indexes
        $this->addSql('CREATE INDEX idx_aggregate_type_id_version ON event_store (aggregate_type, aggregate_id, version)');
        $this->addSql('CREATE INDEX idx_aggregate_type_occurred_at ON event_store (aggregate_type, occurred_at)');
        $this->addSql('CREATE INDEX idx_occurred_at ON event_store (occurred_at)');
    }
}