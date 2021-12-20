<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\RunSource;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211220155048 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create table for ' . RunSource::class;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE run_source (
                id VARCHAR(32) NOT NULL, 
                parent_id VARCHAR(32) NOT NULL, 
                parameters TEXT DEFAULT NULL,
                PRIMARY KEY(id)
            )
        ');
        $this->addSql('COMMENT ON COLUMN run_source.parameters IS \'(DC2Type:simple_array)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_55B64E4E727ACA70 ON run_source (parent_id)');
        $this->addSql('
            ALTER TABLE run_source 
                ADD CONSTRAINT FK_55B64E4E727ACA70 
                FOREIGN KEY (parent_id) REFERENCES source (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        ');
        $this->addSql('
            ALTER TABLE run_source 
                ADD CONSTRAINT FK_55B64E4EBF396750 
                FOREIGN KEY (id) REFERENCES source (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE run_source');
    }
}
