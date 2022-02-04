<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\FileSource;
use App\Entity\RunSource;
use App\Services\RunSourceSerializer;
use App\Tests\Model\UserId;
use App\Tests\Services\FileStoreFixtureCreator;
use App\Tests\Services\FixtureLoader;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RunSourceSerializerTest extends WebTestCase
{
    private RunSourceSerializer $runSourceSerializer;
    private FileStoreFixtureCreator $fixtureCreator;
    private FixtureLoader $fixtureLoader;

    protected function setUp(): void
    {
        parent::setUp();

        $runSourceSerializer = self::getContainer()->get(RunSourceSerializer::class);
        \assert($runSourceSerializer instanceof RunSourceSerializer);
        $this->runSourceSerializer = $runSourceSerializer;

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
    public function testSerializeSuccess(string $fixtureSetIdentifier, callable $expectedCreator): void
    {
        $fileSource = new FileSource(UserId::create(), 'file source label');
        $source = new RunSource($fileSource);

        $this->fixtureCreator->copyFixtureSetTo($fixtureSetIdentifier, (string) $source);

        $content = $this->runSourceSerializer->serialize($source);

        self::assertSame($expectedCreator($this->fixtureLoader), $content);
    }

    /**
     * @return array<mixed>
     */
    public function serializeSuccessDataProvider(): array
    {
        return [
            'yml_yaml_valid' => [
                'fixtureSetIdentifier' => 'yml_yaml_valid',
                'expectedCreator' => function (FixtureLoader $fixtureLoader): string {
                    return $fixtureLoader->load('/RunSource/source_yml_yaml.yaml');
                },
            ],
        ];
    }
}
