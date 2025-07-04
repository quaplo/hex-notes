<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration for aggregate snapshots table
 */
final class Version20250703215000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create aggregate_snapshots table for event sourcing snapshots';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE aggregate_snapshots (
                aggregate_id VARCHAR(36) NOT NULL,
                aggregate_type VARCHAR(100) NOT NULL,
                version INT NOT NULL,
                data JSONB NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (aggregate_id, aggregate_type)
            )
        ');
        
        $this->addSql('CREATE INDEX idx_aggregate_snapshots_type_version ON aggregate_snapshots (aggregate_type, version)');
        $this->addSql('CREATE INDEX idx_aggregate_snapshots_created ON aggregate_snapshots (created_at)');
        
        // Add PostgreSQL comments
        $this->addSql('COMMENT ON TABLE aggregate_snapshots IS \'Snapshots for Event Sourcing aggregates - stores periodic state snapshots for performance optimization\'');
        $this->addSql('COMMENT ON COLUMN aggregate_snapshots.aggregate_id IS \'UUID of the aggregate\'');
        $this->addSql('COMMENT ON COLUMN aggregate_snapshots.aggregate_type IS \'Type of aggregate (e.g., Project, User)\'');
        $this->addSql('COMMENT ON COLUMN aggregate_snapshots.version IS \'Version/sequence number of the snapshot\'');
        $this->addSql('COMMENT ON COLUMN aggregate_snapshots.data IS \'Serialized aggregate state\'');
        $this->addSql('COMMENT ON COLUMN aggregate_snapshots.created_at IS \'When the snapshot was created\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS aggregate_snapshots');
    }
}