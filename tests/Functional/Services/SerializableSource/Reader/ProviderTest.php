<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\SerializableSource\Reader;

use App\Entity\FileSource;
use App\Exception\SerializableSourceReaderNotFoundException;
use App\Model\SerializableSourceInterface;
use App\Model\UserGitRepository;
use App\Services\SerializableSource\Reader\Provider;
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
        $serializableSource = \Mockery::mock(SerializableSourceInterface::class);

        $this->expectExceptionObject(new SerializableSourceReaderNotFoundException($serializableSource));

        $this->provider->find($serializableSource);
    }

    /**
     * @dataProvider findSuccessDataProvider
     */
    public function testFindSuccess(
        SerializableSourceInterface $serializableSource,
        string $expectedReaderServiceId,
    ): void {
        $reader = $this->provider->find($serializableSource);

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
                'serializableSource' => \Mockery::mock(FileSource::class),
                'expectedReaderServiceId' => 'file_source.storage',
            ],
            'git repository' => [
                'serializableSource' => \Mockery::mock(UserGitRepository::class),
                'expectedReaderServiceId' => 'git_repository.storage',
            ],
        ];
    }
}
