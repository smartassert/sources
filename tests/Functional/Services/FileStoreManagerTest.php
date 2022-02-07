<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\FileSource;
use App\Services\FileStoreManager;
use App\Tests\Model\UserId;
use App\Tests\Services\FileStoreFixtureCreator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Path;

class FileStoreManagerTest extends WebTestCase
{
    private FileStoreManager $fileStoreManager;
    private FileStoreFixtureCreator $fixtureCreator;
    private string $fileStoreBasePath;

    protected function setUp(): void
    {
        parent::setUp();

        $fileStoreManager = self::getContainer()->get(FileStoreManager::class);
        \assert($fileStoreManager instanceof FileStoreManager);
        $this->fileStoreManager = $fileStoreManager;

        $fixtureCreator = self::getContainer()->get(FileStoreFixtureCreator::class);
        \assert($fixtureCreator instanceof FileStoreFixtureCreator);
        $this->fixtureCreator = $fixtureCreator;

        $fileStoreBasePath = self::getContainer()->getParameter('file_store_base_path');
        \assert(is_string($fileStoreBasePath));
        $this->fileStoreBasePath = $fileStoreBasePath;
    }

    public function testCreateRemoveSuccess(): void
    {
        $relativePath = UserId::create();
        $absolutePath = (string) $this->fileStoreManager->createAbsolutePath($relativePath);
        self::assertDirectoryDoesNotExist($absolutePath);

        $this->fileStoreManager->create($relativePath);
        self::assertDirectoryExists($absolutePath);

        $this->fileStoreManager->remove($relativePath);
        self::assertDirectoryDoesNotExist($absolutePath);
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
                    $basePath . '/directory/file3.txt',
                    $basePath . '/file1.txt',
                    $basePath . '/file2.txt',
                ],
            ],
            'source: txt, extensions=[txt]' => [
                'fixtureSet' => 'txt',
                'relativePath' => $basePath,
                'extensions' => ['txt'],
                'expected' => [
                    $basePath . '/directory/file3.txt',
                    $basePath . '/file1.txt',
                    $basePath . '/file2.txt',
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
                    $basePath . '/directory/file3.yml',
                    $basePath . '/file1.yaml',
                    $basePath . '/file2.yml',
                ],
            ],
            'source: yml_yaml, extensions=[yml]' => [
                'fixtureSet' => 'yml_yaml_valid',
                'relativePath' => $basePath,
                'extensions' => ['yml'],
                'expected' => [
                    $basePath . '/directory/file3.yml',
                    $basePath . '/file2.yml',
                ],
            ],
            'source: mixed, extensions=[yaml]' => [
                'fixtureSet' => 'mixed',
                'relativePath' => $basePath,
                'extensions' => ['yaml'],
                'expected' => [
                    $basePath . '/directory/file3.yaml',
                    $basePath . '/file1.yaml',
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

        $expectedAbsolutePath = Path::canonicalize($this->createFileStoreAbsolutePath($fileRelativePath));

        self::assertFileExists($expectedAbsolutePath);
        self::assertSame($content, file_get_contents($expectedAbsolutePath));

        unlink($expectedAbsolutePath);
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
        $this->fixtureCreator->copySetTo('/Source/txt', (string) $fileSource);

        $fileRelativePath = $fileSource . '/file1.txt';

        self::assertSame('File One' . "\n", $this->fileStoreManager->read($fileRelativePath));
    }

    private function createFileStoreAbsolutePath(string $relativePath): string
    {
        return Path::canonicalize($this->fileStoreBasePath . '/' . $relativePath);
    }

//    public function testFlyFilesystemScope(): void
//    {
//        $flyFilesystem = self::getContainer()->get(Filesystem::class);
//
//        $flyFilesystem->write('foo01.txt', 'foo01');
//        $flyFilesystem->write('./foo02.txt', 'foo02');
//        $flyFilesystem->write('../foo03.txt', 'foo03');
//    }
}
