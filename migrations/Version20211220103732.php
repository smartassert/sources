<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\AbstractSource;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211220103732 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create table for ' . AbstractSource::class;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE source (
                id VARCHAR(32) NOT NULL, 
                type_id INT NOT NULL, 
                user_id VARCHAR(32) NOT NULL, 
                type VARCHAR(255) NOT NULL, 
                PRIMARY KEY(id)
            )
        ');
        $this->addSql('CREATE INDEX IDX_5F8A7F73C54C8C93 ON source (type_id)');
        $this->addSql('
            ALTER TABLE source 
                ADD CONSTRAINT FK_5F8A7F73C54C8C93 FOREIGN KEY (type_id) 
                REFERENCES source_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE source DROP CONSTRAINT FK_5F8A7F73C54C8C93');
        $this->addSql('DROP TABLE source');
    }
}
