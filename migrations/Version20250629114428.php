<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250629114428 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        
        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
            $this->addSql('ALTER TABLE projects ADD CONSTRAINT FK_5C93B3A4DE12AB56 FOREIGN KEY (created_by) REFERENCES users (id)');
        }
        
        $this->addSql('CREATE INDEX IDX_5C93B3A4DE12AB56 ON projects (created_by)');
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        
        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
            $this->addSql('ALTER TABLE projects DROP CONSTRAINT FK_5C93B3A4DE12AB56');
        }
        
        $this->addSql('DROP INDEX IDX_5C93B3A4DE12AB56');
    }
}
