<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211213120807 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create source table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE source (
                id VARCHAR(32) NOT NULL, 
                user_id VARCHAR(32) NOT NULL, 
                host_url VARCHAR(255) NOT NULL, 
                path VARCHAR(255) NOT NULL, 
                access_token VARCHAR(255) DEFAULT NULL, 
                PRIMARY KEY(id)
            )
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE source');
    }
}
