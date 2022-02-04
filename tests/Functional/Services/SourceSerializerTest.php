<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\FileSource;
use App\Entity\RunSource;
use App\Services\SourceSerializer;
use App\Tests\Model\UserId;
use App\Tests\Services\FileStoreFixtureCreator;
use App\Tests\Services\FixtureLoader;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SourceSerializerTest extends WebTestCase
{
    private SourceSerializer $sourceSerializer;
    private FileStoreFixtureCreator $fixtureCreator;
    private FixtureLoader $fixtureLoader;

    protected function setUp(): void
    {
        parent::setUp();

        $sourceSerializer = self::getContainer()->get(SourceSerializer::class);
        \assert($sourceSerializer instanceof SourceSerializer);
        $this->sourceSerializer = $sourceSerializer;

        $fixtureCreator = self::getContainer()->get(FileStoreFixtureCreator::class);
        \assert($fixtureCreator instanceof FileStoreFixtureCreator);
        $this->fixtureCreator = $fixtureCreator;

        $fixtureLoader = self::getContainer()->get(FixtureLoader::class);
        \assert($fixtureLoader instanceof FixtureLoader);
        $this->fixtureLoader = $fixtureLoader;
    }

    /**
     * @dataProvider serializeSuccessDataProvider
     */
    public function testSerializeSuccess(string $fixtureSetIdentifier, ?string $path, callable $expectedCreator): void
    {
        $fileSource = new FileSource(UserId::create(), 'file source label');
        $source = new RunSource($fileSource);

        $this->fixtureCreator->copyFixtureSetTo($fixtureSetIdentifier, (string) $source);

        $content = $this->sourceSerializer->serialize($source, $path);

        self::assertSame($expectedCreator($this->fixtureLoader), $content);
    }

    /**
     * @return array<mixed>
     */
    public function serializeSuccessDataProvider(): array
    {
        return [
            'yml_yaml_valid, entire' => [
                'fixtureSetIdentifier' => 'yml_yaml_valid',
                'path' => null,
                'expectedCreator' => function (FixtureLoader $fixtureLoader): string {
                    return $fixtureLoader->load('/RunSource/source_yml_yaml_entire.yaml');
                },
            ],
            'yml_yaml_valid, sub-directory without leading slash' => [
                'fixtureSetIdentifier' => 'yml_yaml_valid',
                'path' => 'directory',
                'expectedCreator' => function (FixtureLoader $fixtureLoader): string {
                    return $fixtureLoader->load('/RunSource/source_yml_yaml_partial.yaml');
                },
            ],
            'yml_yaml_valid, sub-directory with leading slash' => [
                'fixtureSetIdentifier' => 'yml_yaml_valid',
                'path' => '/directory',
                'expectedCreator' => function (FixtureLoader $fixtureLoader): string {
                    return $fixtureLoader->load('/RunSource/source_yml_yaml_partial.yaml');
                },
            ],
        ];
    }
}
