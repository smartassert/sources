<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\SerializedSuite;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230308102047 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create table for ' . SerializedSuite::class;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE serialized_suite (
                id VARCHAR(32) NOT NULL, 
                suite_id VARCHAR(32) NOT NULL, 
                parameters JSON DEFAULT NULL, 
                state VARCHAR(255) NOT NULL, 
                failure_reason VARCHAR(255) DEFAULT NULL, 
                failure_message TEXT DEFAULT NULL, 
                PRIMARY KEY(id)
            )
        ');
        $this->addSql('CREATE INDEX IDX_81B93DD14FFCB518 ON serialized_suite (suite_id)');
        $this->addSql('
            ALTER TABLE serialized_suite 
                ADD CONSTRAINT FK_81B93DD14FFCB518 FOREIGN KEY (suite_id) 
                REFERENCES suite (id) NOT DEFERRABLE INITIALLY IMMEDIATE
       ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE serialized_suite DROP CONSTRAINT FK_81B93DD14FFCB518');
        $this->addSql('DROP TABLE serialized_suite');
    }
}
