<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\SourceType;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211220103730 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create table for ' . SourceType::class;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE source_type_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('
            CREATE TABLE source_type (
                id INT NOT NULL, 
                name VARCHAR(32) NOT NULL, 
                PRIMARY KEY(id)
            )
        ');
        $this->addSql('CREATE UNIQUE INDEX uniq_8d54d22a5e237e06 ON source_type (name)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE source_type_id_seq CASCADE');
        $this->addSql('DROP TABLE source_type');
    }
}
