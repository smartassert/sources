<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\SourceRepository\Reader;

use App\Entity\FileSource;
use App\Exception\SourceRepositoryReaderNotFoundException;
use App\Model\SourceRepositoryInterface;
use App\Model\UserGitRepository;
use App\Services\SourceRepository\Reader\Provider;
use League\Flysystem\FilesystemReader;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProviderTest extends WebTestCase
{
    private Provider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $provider = self::getContainer()->get(Provider::class);
        \assert($provider instanceof Provider);
        $this->provider = $provider;
    }

    public function testFindReaderNotFound(): void
    {
        $sourceRepository = \Mockery::mock(SourceRepositoryInterface::class);

        $this->expectExceptionObject(new SourceRepositoryReaderNotFoundException($sourceRepository));

        $this->provider->find($sourceRepository);
    }

    /**
     * @dataProvider findSuccessDataProvider
     */
    public function testFindSuccess(
        SourceRepositoryInterface $sourceRepository,
        string $expectedReaderServiceId,
    ): void {
        $reader = $this->provider->find($sourceRepository);

        self::assertInstanceOf(FilesystemReader::class, $reader);
        self::assertSame(
            self::getContainer()->get($expectedReaderServiceId),
            $reader
        );
    }

    /**
     * @return array<mixed>
     */
    public function findSuccessDataProvider(): array
    {
        return [
            'file source' => [
                'sourceRepository' => \Mockery::mock(FileSource::class),
                'expectedReaderServiceId' => 'file_source.storage',
            ],
            'git repository' => [
                'sourceRepository' => \Mockery::mock(UserGitRepository::class),
                'expectedReaderServiceId' => 'git_repository.storage',
            ],
        ];
    }
}
