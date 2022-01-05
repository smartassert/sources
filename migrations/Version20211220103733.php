<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\GitSource;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211220103733 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create table for ' . GitSource::class;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE git_source (
                id VARCHAR(32) NOT NULL, 
                host_url VARCHAR(255) NOT NULL, 
                path VARCHAR(255) NOT NULL, 
                credentials VARCHAR(255) NOT NULL, 
                PRIMARY KEY(id)
            )
        ');
        $this->addSql('
            ALTER TABLE git_source 
                ADD CONSTRAINT FK_33BE9864BF396750 FOREIGN KEY (id) 
                REFERENCES source (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE git_source DROP CONSTRAINT FK_33BE9864BF396750');
        $this->addSql('DROP TABLE git_source');
    }
}
