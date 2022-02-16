<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\FileSource;
use App\Entity\RunSource;
use App\Services\FileStoreManager;
use App\Services\SourceSerializer;
use App\Tests\Model\UserId;
use App\Tests\Services\FileStoreFixtureCreator;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SourceSerializerTest extends WebTestCase
{
    private SourceSerializer $sourceSerializer;
    private FileStoreFixtureCreator $fixtureCreator;
    private FilesystemOperator $filesystemOperator;
    private FileStoreManager $fixtureFileStore;

    protected function setUp(): void
    {
        parent::setUp();

        $sourceSerializer = self::getContainer()->get(SourceSerializer::class);
        \assert($sourceSerializer instanceof SourceSerializer);
        $this->sourceSerializer = $sourceSerializer;

        $fixtureCreator = self::getContainer()->get(FileStoreFixtureCreator::class);
        \assert($fixtureCreator instanceof FileStoreFixtureCreator);
        $this->fixtureCreator = $fixtureCreator;

        $filesystemOperator = self::getContainer()->get('default.storage');
        \assert($filesystemOperator instanceof FilesystemOperator);
        $this->filesystemOperator = $filesystemOperator;

        $fixtureFileStore = self::getContainer()->get('app.tests.services.file_store_manager.fixtures');
        \assert($fixtureFileStore instanceof FileStoreManager);
        $this->fixtureFileStore = $fixtureFileStore;
    }

    /**
     * @dataProvider serializeSuccessDataProvider
     */
    public function testSerializeSuccess(
        string $fixtureSetIdentifier,
        ?string $path,
        string $expectedContentFixture
    ): void {
        $fileSource = new FileSource(UserId::create(), 'file source label');
        $source = new RunSource($fileSource);

        $this->fixtureCreator->copySetTo(
            'Source/' . $fixtureSetIdentifier,
            $this->filesystemOperator,
            (string) $source
        );

        $content = $this->sourceSerializer->serialize((string) $source, $path);
        $expected = trim($this->fixtureFileStore->read($expectedContentFixture));

        self::assertSame($expected, $content);
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
                'expectedContentFixture' => 'RunSource/source_yml_yaml_entire.yaml',
            ],
            'yml_yaml_valid, sub-directory without leading slash' => [
                'fixtureSetIdentifier' => 'yml_yaml_valid',
                'path' => 'directory',
                'expectedContentFixture' => 'RunSource/source_yml_yaml_partial.yaml',
            ],
            'yml_yaml_valid, sub-directory with leading slash' => [
                'fixtureSetIdentifier' => 'yml_yaml_valid',
                'path' => '/directory',
                'expectedContentFixture' => 'RunSource/source_yml_yaml_partial.yaml',
            ],
        ];
    }
}
