<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240601UserProjection extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user_projection table for user email uniqueness and projections';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_projection (
            id CHAR(36) NOT NULL PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            created_at TIMESTAMP NOT NULL
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE user_projection');
    }
} 