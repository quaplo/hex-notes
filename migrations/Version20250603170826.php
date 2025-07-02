<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250603170826 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Use platform-specific SQL for better compatibility
        $platform = $this->connection->getDatabasePlatform();
        
        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
            $this->addSql(<<<'SQL'
                CREATE TABLE projects (id UUID NOT NULL, name VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))
            SQL);
            $this->addSql(<<<'SQL'
                CREATE TABLE users (id UUID NOT NULL, email VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
            SQL);
        } else {
            // SQLite compatible version
            $this->addSql(<<<'SQL'
                CREATE TABLE projects (id VARCHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, PRIMARY KEY(id))
            SQL);
            $this->addSql(<<<'SQL'
                CREATE TABLE users (id VARCHAR(36) NOT NULL, email VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id))
            SQL);
        }
        
        $this->addSql(<<<'SQL'
            CREATE TABLE user_project (project_entity_id VARCHAR(36) NOT NULL, user_entity_id VARCHAR(36) NOT NULL, PRIMARY KEY(project_entity_id, user_entity_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_77BECEE49019388A ON user_project (project_entity_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_77BECEE481C5F0B9 ON user_project (user_entity_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)
        SQL);
        
        // Foreign key constraints - only for PostgreSQL (SQLite has issues with ALTER TABLE ADD CONSTRAINT)
        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
            $this->addSql('ALTER TABLE user_project ADD CONSTRAINT FK_77BECEE49019388A FOREIGN KEY (project_entity_id) REFERENCES projects (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE user_project ADD CONSTRAINT FK_77BECEE481C5F0B9 FOREIGN KEY (user_entity_id) REFERENCES users (id) ON DELETE CASCADE');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE user_project DROP CONSTRAINT FK_77BECEE49019388A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_project DROP CONSTRAINT FK_77BECEE481C5F0B9
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE projects
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_project
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE users
        SQL);
    }
}
