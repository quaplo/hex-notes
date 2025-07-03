<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create project_read_model table for optimized queries
 */
final class Version20250703210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create project_read_model table for optimized queries';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE project_read_model (
                id VARCHAR(36) NOT NULL PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                owner_id VARCHAR(36) NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                workers JSON NOT NULL DEFAULT \'[]\',
                version INT NOT NULL DEFAULT 0
            )
        ');

        $this->addSql('CREATE INDEX idx_project_owner ON project_read_model (owner_id)');
        $this->addSql('CREATE INDEX idx_project_deleted ON project_read_model (deleted_at)');
        $this->addSql('COMMENT ON COLUMN project_read_model.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN project_read_model.deleted_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS project_read_model');
    }
}