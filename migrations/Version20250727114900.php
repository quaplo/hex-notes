<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add aggregate_type column to event_store for domain separation and optimization
 */
final class Version20250727114900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add aggregate_type column to event_store table with indexes for domain separation';
    }

    public function up(Schema $schema): void
    {
        // Add aggregate_type column as nullable first
        $this->addSql('ALTER TABLE event_store ADD COLUMN aggregate_type VARCHAR(255) DEFAULT NULL');
        
        // Populate existing records with aggregate_type based on event_type namespace
        // Project events: App\Project\Domain\Event\*
        $this->addSql("UPDATE event_store SET aggregate_type = 'App\\\\Project' WHERE event_type LIKE 'App\\\\Project\\\\Domain\\\\Event\\\\%'");
        
        // User events: App\User\Domain\Event\*
        $this->addSql("UPDATE event_store SET aggregate_type = 'App\\\\User' WHERE event_type LIKE 'App\\\\User\\\\Domain\\\\Event\\\\%'");
        
        // Shared events: App\Shared\Domain\Event\*
        $this->addSql("UPDATE event_store SET aggregate_type = 'App\\\\Shared' WHERE event_type LIKE 'App\\\\Shared\\\\Domain\\\\Event\\\\%'");
        
        // Make aggregate_type NOT NULL after population
        $this->addSql('ALTER TABLE event_store ALTER COLUMN aggregate_type SET NOT NULL');
        
        // Drop existing indexes
        $this->addSql('DROP INDEX idx_aggregate_version');
        $this->addSql('DROP INDEX idx_occurred_at');
        
        // Create new optimized indexes with aggregate_type
        $this->addSql('CREATE INDEX idx_aggregate_type_id_version ON event_store (aggregate_type, aggregate_id, version)');
        $this->addSql('CREATE INDEX idx_aggregate_type_occurred_at ON event_store (aggregate_type, occurred_at)');
        $this->addSql('CREATE INDEX idx_occurred_at ON event_store (occurred_at)');
    }

    public function down(Schema $schema): void
    {
        // Drop new indexes
        $this->addSql('DROP INDEX idx_aggregate_type_id_version');
        $this->addSql('DROP INDEX idx_aggregate_type_occurred_at');
        $this->addSql('DROP INDEX idx_occurred_at');
        
        // Recreate original indexes
        $this->addSql('CREATE INDEX idx_aggregate_version ON event_store (aggregate_id, version)');
        $this->addSql('CREATE INDEX idx_occurred_at ON event_store (occurred_at)');
        
        // Remove aggregate_type column
        $this->addSql('ALTER TABLE event_store DROP COLUMN aggregate_type');
    }
}