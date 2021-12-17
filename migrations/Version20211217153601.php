<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Source;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211217153601 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create table for ' . Source::class;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE source (
                id VARCHAR(32) NOT NULL, 
                type_id INT NOT NULL, 
                user_id VARCHAR(32) NOT NULL, 
                host_url VARCHAR(255) NOT NULL, 
                path VARCHAR(255) NOT NULL, 
                access_token VARCHAR(255) DEFAULT NULL, 
                ref VARCHAR(255) DEFAULT NULL,
                PRIMARY KEY(id)
            )
        ');
        $this->addSql('CREATE INDEX IDX_5F8A7F73C54C8C93 ON source (type_id)');
        $this->addSql('
            ALTER TABLE source 
                ADD CONSTRAINT FK_5F8A7F73C54C8C93 FOREIGN KEY (type_id) REFERENCES source_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE source');
    }
}
