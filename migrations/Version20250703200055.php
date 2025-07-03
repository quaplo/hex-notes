<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250703200055 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove unused user_project and user_projection tables from old event sourcing architecture';
    }

    public function up(Schema $schema): void
    {
        // Drop user_project table (no longer used after moving away from user event sourcing)
        $this->addSql('DROP TABLE IF EXISTS user_project CASCADE');
        
        // Drop user_projection table (no longer used after moving away from user event sourcing)
        $this->addSql('DROP TABLE IF EXISTS user_projection CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Recreate user_projection table
        $this->addSql('CREATE TABLE user_projection (
            id CHAR(36) NOT NULL PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            created_at TIMESTAMP NOT NULL
        )');
        
        // Recreate user_project table with latest structure
        $this->addSql('CREATE TABLE user_project (
            role VARCHAR(50) NOT NULL,
            project_id UUID NOT NULL,
            user_id UUID NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            added_by UUID NOT NULL,
            PRIMARY KEY(project_id, user_id)
        )');
        
        // Recreate indexes
        $this->addSql('CREATE INDEX IDX_77BECEE4166D1F9C ON user_project (project_id)');
        $this->addSql('CREATE INDEX IDX_77BECEE4A76ED395 ON user_project (user_id)');
        $this->addSql('CREATE INDEX IDX_77BECEE4699B6BAF ON user_project (added_by)');
        
        // Recreate foreign key constraints (only if tables exist)
        $this->addSql('ALTER TABLE user_project ADD CONSTRAINT FK_77BECEE4166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_project ADD CONSTRAINT FK_77BECEE4A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_project ADD CONSTRAINT FK_77BECEE4699B6BAF FOREIGN KEY (added_by) REFERENCES users (id) ON DELETE CASCADE');
    }
}
