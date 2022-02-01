<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\RunSourcePreparation;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220201154200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create table for ' . RunSourcePreparation::class;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE run_source_preparation (
                run_source_id VARCHAR(32) NOT NULL, 
                state VARCHAR(255) NOT NULL, 
                failure_reason VARCHAR(255) DEFAULT NULL, 
                failure_message TEXT NOT NULL, 
                PRIMARY KEY(run_source_id)
            )
        ');

        $this->addSql('
            ALTER TABLE run_source_preparation 
                ADD CONSTRAINT FK_410CB14A727ACA70 
                    FOREIGN KEY (run_source_id) REFERENCES source (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE run_source_preparation');
    }
}
