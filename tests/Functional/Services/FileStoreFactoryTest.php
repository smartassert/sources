<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Model\FileLocatorInterface;
use App\Model\FileStore;
use App\Services\FileStoreFactory;
use App\Tests\Model\UserId;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FileStoreFactoryTest extends WebTestCase
{
    private FileStoreFactory $factory;
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $factory = self::getContainer()->get(FileStoreFactory::class);
        \assert($factory instanceof FileStoreFactory);
        $this->factory = $factory;

        $basePath = self::getContainer()->getParameter('file_store_base_path');
        \assert(is_string($basePath));
        $this->basePath = $basePath;
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param callable(string): FileStore $expectedFileStoreCreator
     */
    public function testCreate(FileLocatorInterface $fileLocator, callable $expectedFileStoreCreator): void
    {
        self::assertEquals(
            $expectedFileStoreCreator($this->basePath),
            $this->factory->create($fileLocator)
        );
    }

    /**
     * @return array<mixed>
     */
    public function createDataProvider(): array
    {
        $fileSource = new FileSource(UserId::create(), 'file source label');
        $gitSource = new GitSource(UserId::create(), 'https://example.com/repository.git', '/');
        $fileRunSource = new RunSource($fileSource);
        $gitRunSource = new RunSource($gitSource);

        return [
            FileSource::class => [
                'fileLocator' => $fileSource,
                'expectedFileStoreCreator' => function (string $basePath) use ($fileSource): FileStore {
                    return new FileStore($basePath, $fileSource);
                },
            ],
            RunSource::class . ' encapsulating ' . FileSource::class => [
                'fileLocator' => $fileRunSource,
                'expectedFileStoreCreator' => function (string $basePath) use ($fileRunSource): FileStore {
                    return new FileStore($basePath, $fileRunSource);
                },
            ],
            RunSource::class . ' encapsulating ' . GitSource::class => [
                'fileLocator' => $gitRunSource,
                'expectedFileStoreCreator' => function (string $basePath) use ($gitRunSource): FileStore {
                    return new FileStore($basePath, $gitRunSource);
                },
            ],
        ];
    }
}