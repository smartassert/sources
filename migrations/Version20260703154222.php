<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\SerializedSuite;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260703154222 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ' . SerializedSuite::class . '.notifyUrl';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE serialized_suite ADD notify_url VARCHAR(255)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE serialized_suite DROP notify_url');
    }
}
