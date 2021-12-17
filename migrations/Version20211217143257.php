<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\SourceType;
use App\Repository\SourceTypeRepository;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use App\Migrations\DependsOnServices;
use Doctrine\ORM\EntityManagerInterface;

#[DependsOnServices([
    'setSourceTypeRepository' => SourceTypeRepository::class,
    'setEntityManager' => EntityManagerInterface::class
])]
final class Version20211217143257 extends AbstractMigration
{
    private EntityManagerInterface $entityManager;
    private SourceTypeRepository $sourceTypeRepository;

    public function getDescription(): string
    {
        return 'Create fixtures for ' . SourceType::class;
    }

    public function setEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->entityManager = $entityManager;
    }

    public function setSourceTypeRepository(SourceTypeRepository $sourceTypeRepository): void
    {
        $this->sourceTypeRepository = $sourceTypeRepository;
    }

    public function up(Schema $schema): void
    {
        foreach (SourceType::ALL as $typeName) {
            $has = $this->sourceTypeRepository->findOneByName($typeName) instanceof SourceType;
            if (false === $has) {
                $sourceType = new SourceType($typeName);
                $this->entityManager->persist($sourceType);
                $this->entityManager->flush();
            }
        }
    }

    public function down(Schema $schema): void
    {
    }
}
