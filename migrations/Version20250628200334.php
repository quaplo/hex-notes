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
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE user_project ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_project ADD added_by UUID NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_project ADD CONSTRAINT FK_77BECEE4699B6BAF FOREIGN KEY (added_by) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_77BECEE4699B6BAF ON user_project (added_by)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE user_project DROP CONSTRAINT FK_77BECEE4699B6BAF
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_77BECEE4699B6BAF
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_project DROP created_at
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_project DROP added_by
        SQL);
    }
}
