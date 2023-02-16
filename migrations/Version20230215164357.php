<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Suite;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230215164357 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create table for ' . Suite::class;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE suite (
                id VARCHAR(32) NOT NULL,
                source_id VARCHAR(32) NOT NULL,
                label VARCHAR(255) NOT NULL,
                tests TEXT NOT NULL,
                deleted_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
                PRIMARY KEY(id)
           )
       ');
        $this->addSql('CREATE INDEX IDX_153CE426953C1C61 ON suite (source_id)');
        $this->addSql('COMMENT ON COLUMN suite.tests IS \'(DC2Type:simple_array)\'');
        $this->addSql('COMMENT ON COLUMN suite.deleted_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('
            ALTER TABLE suite 
                ADD CONSTRAINT FK_153CE426953C1C61 
                FOREIGN KEY (source_id) REFERENCES source (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE suite DROP CONSTRAINT FK_153CE426953C1C61');
        $this->addSql('DROP TABLE suite');
    }
}
