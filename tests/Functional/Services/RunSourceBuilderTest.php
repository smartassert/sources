<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\FileSource;
use App\Entity\RunSource;
use App\Services\RunSourceBuilder;
use App\Tests\Model\UserId;
use App\Tests\Services\FileStoreFixtureCreator;
use App\Tests\Services\FixtureLoader;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RunSourceBuilderTest extends WebTestCase
{
    private RunSourceBuilder $runSourceBuilder;
    private FileStoreFixtureCreator $fixtureCreator;
    private FixtureLoader $fixtureLoader;

    protected function setUp(): void
    {
        parent::setUp();

        $runSourceBuilder = self::getContainer()->get(RunSourceBuilder::class);
        \assert($runSourceBuilder instanceof RunSourceBuilder);
        $this->runSourceBuilder = $runSourceBuilder;

        $fixtureCreator = self::getContainer()->get(FileStoreFixtureCreator::class);
        \assert($fixtureCreator instanceof FileStoreFixtureCreator);
        $this->fixtureCreator = $fixtureCreator;

        $fixtureLoader = self::getContainer()->get(FixtureLoader::class);
        \assert($fixtureLoader instanceof FixtureLoader);
        $this->fixtureLoader = $fixtureLoader;
    }

    /**
     * @dataProvider buildSuccessDataProvider
     */
    public function testBuildSuccess(string $fixtureSetIdentifier, callable $expectedCreator): void
    {
        $fileSource = new FileSource(UserId::create(), 'file source label');
        $source = new RunSource($fileSource);

        $this->fixtureCreator->copyFixtureSetTo($fixtureSetIdentifier, (string) $source);

        $content = $this->runSourceBuilder->build($source);

        self::assertSame($expectedCreator($this->fixtureLoader), $content);
    }

    /**
     * @return array<mixed>
     */
    public function buildSuccessDataProvider(): array
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
