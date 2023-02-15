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
                user_id VARCHAR(32) NOT NULL,
                deleted_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, 
                type VARCHAR(32) NOT NULL, 
                PRIMARY KEY(id)
            )
        ');
        $this->addSql('COMMENT ON COLUMN source.deleted_at IS \'(DC2Type:datetimetz_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE source');
    }
}
