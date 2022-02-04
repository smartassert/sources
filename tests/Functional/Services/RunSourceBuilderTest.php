<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\FileSource;
use App\Entity\RunSource;
use App\Services\RunSourceBuilder;
use App\Tests\Model\UserId;
use App\Tests\Services\FileStoreFixtureCreator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RunSourceBuilderTest extends WebTestCase
{
    private RunSourceBuilder $runSourceBuilder;
    private FileStoreFixtureCreator $fixtureCreator;

    protected function setUp(): void
    {
        parent::setUp();

        $runSourceBuilder = self::getContainer()->get(RunSourceBuilder::class);
        \assert($runSourceBuilder instanceof RunSourceBuilder);
        $this->runSourceBuilder = $runSourceBuilder;

        $fixtureCreator = self::getContainer()->get(FileStoreFixtureCreator::class);
        \assert($fixtureCreator instanceof FileStoreFixtureCreator);
        $this->fixtureCreator = $fixtureCreator;
    }

    /**
     * @dataProvider buildSuccessDataProvider
     */
    public function testBuildSuccess(string $fixtureSetIdentifier, string $expected): void
    {
        $fileSource = new FileSource(UserId::create(), 'file source label');
        $source = new RunSource($fileSource);

        $this->fixtureCreator->copyFixtureSetTo($fixtureSetIdentifier, (string) $source);

        $content = $this->runSourceBuilder->build($source);

        self::assertSame($expected, $content);
    }

    /**
     * @return array<mixed>
     */
    public function buildSuccessDataProvider(): array
    {
        return [
            'source_yml_yaml' => [
                'fixtureSetIdentifier' => 'source_yml_yaml',
                'expected' => <<< 'END'
                    ---
                    path: "directory/file3.yml"
                    content_hash: "3e4280d968d34f0ba48296049cf9c88f"
                    ...
                    ---
                    - "file 3 line 1"
                    ...
                    ---
                    path: "file1.yaml"
                    content_hash: "602226b0406e64e590352b2909029802"
                    ...
                    ---
                    - "file 1 line 1"
                    - "file 1 line 2"
                    ...
                    ---
                    path: "file2.yml"
                    content_hash: "6c8ab92eb84c36b79607cb1d0d3f5037"
                    ...
                    ---
                    - "file 2 line 1"
                    - "file 2 line 2"
                    ...
                    END,
            ],
        ];
    }
}
