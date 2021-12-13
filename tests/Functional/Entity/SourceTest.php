<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\Source;
use App\Repository\SourceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SourceTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private SourceRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        \assert($entityManager instanceof EntityManagerInterface);
        $this->entityManager = $entityManager;

        $repository = self::getContainer()->get(SourceRepository::class);
        \assert($repository instanceof SourceRepository);
        $this->repository = $repository;

        $this->removeAllSources();
    }

    public function testEntityMapping(): void
    {
        self::assertCount(0, $this->repository->findAll());

        $sourceId = '01FPSVJMEBWJCVJGWN3WDVT2Q8';
        $userId = '01FPSVJ7ZT85X73BW05EK9B3XG';

        $source = new Source(
            $sourceId,
            $userId,
            'https://github.com/example/example.git',
            '/',
            null
        );

        $this->entityManager->persist($source);
        $this->entityManager->flush();

        $this->entityManager->detach($source);

        $sources = $this->repository->findAll();

        self::assertCount(1, $sources);
        self::assertEquals($source, $sources[0]);
    }

    protected function removeAllSources(): void
    {
        $sources = $this->repository->findAll();

        foreach ($sources as $source) {
            $this->entityManager->remove($source);
        }

        $this->entityManager->flush();
    }
}
