<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250628195849 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        
        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
            // PostgreSQL version with complex constraints
            $this->addSql('ALTER TABLE user_project DROP CONSTRAINT fk_77becee49019388a');
            $this->addSql('ALTER TABLE user_project DROP CONSTRAINT fk_77becee481c5f0b9');
            $this->addSql('DROP INDEX idx_77becee481c5f0b9');
            $this->addSql('DROP INDEX idx_77becee49019388a');
            $this->addSql('ALTER TABLE user_project DROP CONSTRAINT user_project_pkey');
            $this->addSql('ALTER TABLE user_project ADD role VARCHAR(50) NOT NULL');
            $this->addSql('ALTER TABLE user_project ADD project_id UUID NOT NULL');
            $this->addSql('ALTER TABLE user_project ADD user_id UUID NOT NULL');
            $this->addSql('ALTER TABLE user_project DROP project_entity_id');
            $this->addSql('ALTER TABLE user_project DROP user_entity_id');
            $this->addSql('ALTER TABLE user_project ADD CONSTRAINT FK_77BECEE4166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE user_project ADD CONSTRAINT FK_77BECEE4A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
            $this->addSql('CREATE INDEX IDX_77BECEE4166D1F9C ON user_project (project_id)');
            $this->addSql('CREATE INDEX IDX_77BECEE4A76ED395 ON user_project (user_id)');
            $this->addSql('ALTER TABLE user_project ADD PRIMARY KEY (project_id, user_id)');
        } else {
            // SQLite version - recreate table since ALTER TABLE is limited
            $this->addSql('DROP TABLE user_project');
            $this->addSql('CREATE TABLE user_project (
                project_id VARCHAR(36) NOT NULL,
                user_id VARCHAR(36) NOT NULL,
                role VARCHAR(50) NOT NULL,
                PRIMARY KEY(project_id, user_id)
            )');
            $this->addSql('CREATE INDEX IDX_77BECEE4166D1F9C ON user_project (project_id)');
            $this->addSql('CREATE INDEX IDX_77BECEE4A76ED395 ON user_project (user_id)');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE user_project DROP CONSTRAINT FK_77BECEE4166D1F9C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_project DROP CONSTRAINT FK_77BECEE4A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_77BECEE4166D1F9C
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_77BECEE4A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX user_project_pkey
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_project ADD project_entity_id UUID NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_project ADD user_entity_id UUID NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_project DROP role
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_project DROP project_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_project DROP user_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_project ADD CONSTRAINT fk_77becee49019388a FOREIGN KEY (project_entity_id) REFERENCES projects (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_project ADD CONSTRAINT fk_77becee481c5f0b9 FOREIGN KEY (user_entity_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_77becee481c5f0b9 ON user_project (user_entity_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_77becee49019388a ON user_project (project_entity_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_project ADD PRIMARY KEY (project_entity_id, user_entity_id)
        SQL);
    }
}
