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
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE projects ADD CONSTRAINT FK_5C93B3A4DE12AB56 FOREIGN KEY (created_by) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_5C93B3A4DE12AB56 ON projects (created_by)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE projects DROP CONSTRAINT FK_5C93B3A4DE12AB56
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_5C93B3A4DE12AB56
        SQL);
    }
}
