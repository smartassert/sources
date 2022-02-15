<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\FileSource;
use App\Services\FileStoreManager;
use App\Tests\Model\UserId;
use App\Tests\Services\FileStoreFixtureCreator;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FileStoreManagerTest extends WebTestCase
{
    private FileStoreManager $fileStoreManager;
    private FileStoreFixtureCreator $fixtureCreator;
    private FilesystemOperator $filesystemOperator;

    protected function setUp(): void
    {
        parent::setUp();

        $fileStoreManager = self::getContainer()->get(FileStoreManager::class);
        \assert($fileStoreManager instanceof FileStoreManager);
        $this->fileStoreManager = $fileStoreManager;

        $fixtureCreator = self::getContainer()->get(FileStoreFixtureCreator::class);
        \assert($fixtureCreator instanceof FileStoreFixtureCreator);
        $this->fixtureCreator = $fixtureCreator;

        $filesystemOperator = self::getContainer()->get('default.storage');
        \assert($filesystemOperator instanceof FilesystemOperator);
        $this->filesystemOperator = $filesystemOperator;
    }

    public function testCreateRemoveSuccess(): void
    {
        $relativePath = UserId::create();
        self::assertFalse($this->filesystemOperator->directoryExists($relativePath));

        $this->fileStoreManager->create($relativePath);
        self::assertTrue($this->filesystemOperator->directoryExists($relativePath));

        $this->fileStoreManager->remove($relativePath);
        self::assertFalse($this->filesystemOperator->directoryExists($relativePath));
    }

    /**
     * @dataProvider listDataProvider
     *
     * @param string[] $extensions
     * @param string[] $expectedRelativePathNames
     */
    public function testListSuccess(
        string $fixtureSet,
        string $relativePath,
        array $extensions,
        array $expectedRelativePathNames
    ): void {
        $this->fileStoreManager->remove($relativePath);

        $this->fixtureCreator->copySetTo('/Source/' . $fixtureSet, $relativePath);

        $files = $this->fileStoreManager->list($relativePath, $extensions);

        self::assertCount(count($expectedRelativePathNames), $files);
        self::assertSame($expectedRelativePathNames, $files);
    }

    /**
     * @return array<mixed>
     */
    public function listDataProvider(): array
    {
        $basePath = (string) new FileSource(UserId::create(), '');

        return [
            'source: txt, no extensions' => [
                'fixtureSet' => 'txt',
                'relativePath' => $basePath,
                'extensions' => [],
                'expectedRelativePathNames' => [
                    'directory/file3.txt',
                    'file1.txt',
                    'file2.txt',
                ],
            ],
            'source: txt, extensions=[txt]' => [
                'fixtureSet' => 'txt',
                'relativePath' => $basePath,
                'extensions' => ['txt'],
                'expected' => [
                    'directory/file3.txt',
                    'file1.txt',
                    'file2.txt',
                ],
            ],
            'source: txt, extensions=[yml, yaml]' => [
                'fixtureSet' => 'txt',
                'relativePath' => $basePath,
                'extensions' => ['yml', 'yaml'],
                'expected' => [],
            ],
            'source: yml_yaml, no extensions' => [
                'fixtureSet' => 'yml_yaml_valid',
                'relativePath' => $basePath,
                'extensions' => [],
                'expected' => [
                    'directory/file3.yml',
                    'file1.yaml',
                    'file2.yml',
                ],
            ],
            'source: yml_yaml, extensions=[yml]' => [
                'fixtureSet' => 'yml_yaml_valid',
                'relativePath' => $basePath,
                'extensions' => ['yml'],
                'expected' => [
                    'directory/file3.yml',
                    'file2.yml',
                ],
            ],
            'source: mixed, extensions=[yaml]' => [
                'fixtureSet' => 'mixed',
                'relativePath' => $basePath,
                'extensions' => ['yaml'],
                'expected' => [
                    'directory/file3.yaml',
                    'file1.yaml',
                ],
            ],
        ];
    }

    /**
     * @dataProvider writeDataProvider
     */
    public function testWrite(string $fileRelativePath, string $content): void
    {
        $this->fileStoreManager->write($fileRelativePath, $content);

        self::assertTrue($this->filesystemOperator->fileExists($fileRelativePath));
        self::assertSame($content, $this->filesystemOperator->read($fileRelativePath));
    }

    /**
     * @return array<mixed>
     */
    public function writeDataProvider(): array
    {
        return [
            'single-level relative directory' => [
                'fileRelativePath' => UserId::create() . '/file.txt',
                'content' => md5((string) rand()),
            ],
            'multi-level relative directory, file-only file path' => [
                'fileRelativePath' => UserId::create() . '/' . UserId::create() . '/file.txt',
                'content' => md5((string) rand()),
            ],
        ];
    }

    public function testReadSuccess(): void
    {
        $fileSource = new FileSource(UserId::create(), 'file source label');
        $fileRelativePath = $fileSource . '/' . 'file.txt';
        $content = 'file content';

        $this->filesystemOperator->write($fileRelativePath, $content);

        self::assertSame($content, $this->fileStoreManager->read($fileRelativePath));
    }
}
