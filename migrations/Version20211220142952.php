<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\FileSource;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211220142952 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create table for ' . FileSource::class;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE file_source (
                id VARCHAR(32) NOT NULL, 
                label VARCHAR(255) NOT NULL, 
                PRIMARY KEY(id)
            )
        ');
        $this->addSql('
            ALTER TABLE file_source 
                ADD CONSTRAINT FK_E79D59CCBF396750 FOREIGN KEY (id) 
                REFERENCES source (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE file_source');
    }
}
