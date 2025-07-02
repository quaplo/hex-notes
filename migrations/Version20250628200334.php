<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250628200334 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        
        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
            $this->addSql('ALTER TABLE user_project ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
            $this->addSql('ALTER TABLE user_project ADD added_by UUID NOT NULL');
            $this->addSql('ALTER TABLE user_project ADD CONSTRAINT FK_77BECEE4699B6BAF FOREIGN KEY (added_by) REFERENCES users (id) ON DELETE CASCADE');
        } else {
            // SQLite compatible version
            $this->addSql('ALTER TABLE user_project ADD created_at DATETIME NOT NULL');
            $this->addSql('ALTER TABLE user_project ADD added_by VARCHAR(36) NOT NULL');
        }
        
        $this->addSql('CREATE INDEX IDX_77BECEE4699B6BAF ON user_project (added_by)');
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        
        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
            $this->addSql('ALTER TABLE user_project DROP CONSTRAINT FK_77BECEE4699B6BAF');
        }
        
        $this->addSql('DROP INDEX IDX_77BECEE4699B6BAF');
        $this->addSql('ALTER TABLE user_project DROP created_at');
        $this->addSql('ALTER TABLE user_project DROP added_by');
    }
}
