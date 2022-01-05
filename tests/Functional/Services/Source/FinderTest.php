<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\Source;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Services\Source\Finder;
use App\Services\Source\Store;
use App\Tests\Model\UserId;
use App\Tests\Services\Source\SourceRemover;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FinderTest extends WebTestCase
{
    private Finder $finder;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $finder = self::getContainer()->get(Finder::class);
        \assert($finder instanceof Finder);
        $this->finder = $finder;

        $store = self::getContainer()->get(Store::class);
        \assert($store instanceof Store);
        $this->store = $store;

        $sourceRemover = self::getContainer()->get(SourceRemover::class);
        if ($sourceRemover instanceof SourceRemover) {
            $sourceRemover->removeAll();
        }
    }

    /**
     * @dataProvider findDataProvider
     *
     * @param SourceInterface[] $sources
     */
    public function testFind(array $sources, SourceInterface $source, ?SourceInterface $expected): void
    {
        foreach ($sources as $sourceItem) {
            $this->store->add($sourceItem);
        }

        self::assertEquals(
            $expected,
            $this->finder->find($source)
        );
    }

    /**
     * @return array<mixed>
     */
    public function findDataProvider(): array
    {
        $userId = UserId::create();

        $fileSource = new FileSource($userId, 'file source label');
        $gitSource = new GitSource($userId, 'https://example.com/repository.git', '/');
        $fileRunSourceWithoutParameters = new RunSource($fileSource);
        $fileRunSourceWithParameters = new RunSource($fileSource, ['key1' => 'value1']);
        $gitRunSourceWithoutParameters = new RunSource($gitSource);
        $gitRunSourceWithParameters = new RunSource($gitSource, ['key2' => 'value2']);

        $allSources = [
            $fileSource,
            $gitSource,
            $gitRunSourceWithoutParameters,
            $gitRunSourceWithParameters,
            $fileRunSourceWithoutParameters,
            $fileRunSourceWithParameters,
        ];

        return [
            'no sources, find file source' => [
                'sources' => [],
                'source' => $fileSource,
                'expected' => null,
            ],
            'no sources, find git source' => [
                'sources' => [],
                'source' => $gitSource,
                'expected' => null,
            ],
            'no sources, find file run source' => [
                'sources' => [],
                'source' => $fileRunSourceWithoutParameters,
                'expected' => null,
            ],
            'no sources, find git run source' => [
                'sources' => [],
                'source' => $gitRunSourceWithoutParameters,
                'expected' => null,
            ],
            'find file source, no match' => [
                'sources' => [
                    $gitSource,
                    $gitRunSourceWithoutParameters,
                    $gitRunSourceWithParameters,
                ],
                'source' => $fileSource,
                'expected' => null,
            ],
            'find git source, no match' => [
                'sources' => [
                    $fileSource,
                    $fileRunSourceWithoutParameters,
                    $fileRunSourceWithParameters,
                ],
                'source' => $gitSource,
                'expected' => null,
            ],
            'find file run source without parameters, no match' => [
                'sources' => [
                    $fileSource,
                    $gitSource,
                    $gitRunSourceWithoutParameters,
                    $gitRunSourceWithParameters,
                    $fileRunSourceWithParameters,
                ],
                'source' => $fileRunSourceWithoutParameters,
                'expected' => null,
            ],
            'find git run source without parameters, no match' => [
                'sources' => [
                    $fileSource,
                    $gitSource,
                    $fileRunSourceWithoutParameters,
                    $fileRunSourceWithParameters,
                ],
                'source' => $gitRunSourceWithoutParameters,
                'expected' => null,
            ],
            'find file run source with parameters, no match' => [
                'sources' => [
                    $fileSource,
                    $gitSource,
                    $gitRunSourceWithoutParameters,
                    $gitRunSourceWithParameters,
                    $fileRunSourceWithoutParameters,
                ],
                'source' => $fileRunSourceWithParameters,
                'expected' => null,
            ],
            'find git run source with parameters, no match' => [
                'sources' => [
                    $fileSource,
                    $gitSource,
                    $fileRunSourceWithoutParameters,
                    $fileRunSourceWithoutParameters,
                ],
                'source' => $gitRunSourceWithParameters,
                'expected' => null,
            ],
            'find file source, has match' => [
                'sources' => $allSources,
                'source' => $fileSource,
                'expected' => $fileSource,
            ],
            'find git source, has match' => [
                'sources' => $allSources,
                'source' => $gitSource,
                'expected' => $gitSource,
            ],
            'find file run source without parameters' => [
                'sources' => $allSources,
                'source' => $fileRunSourceWithoutParameters,
                'expected' => null,
            ],
            'find git run source without parameters' => [
                'sources' => $allSources,
                'source' => $gitRunSourceWithoutParameters,
                'expected' => null,
            ],
            'find file run source with parameters' => [
                'sources' => $allSources,
                'source' => $fileRunSourceWithParameters,
                'expected' => null,
            ],
            'find git run source with parameters' => [
                'sources' => $allSources,
                'source' => $gitRunSourceWithParameters,
                'expected' => null,
            ],
        ];
    }
}
