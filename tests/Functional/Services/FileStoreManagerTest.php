<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\FileSource;
use App\Services\FileStoreManager;
use App\Tests\Model\UserId;
use App\Tests\Services\FileStoreFixtureCreator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\SplFileInfo;

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

    public function testExistsCreateRemoveSuccess(): void
    {
        $relativePath = UserId::create();
        $expectedAbsolutePath = $this->createFileStoreAbsolutePath($relativePath);
        self::assertFalse($this->fileStoreManager->exists($relativePath));

        $createdPath = $this->fileStoreManager->create($relativePath);
        self::assertSame($expectedAbsolutePath, $createdPath);
        self::assertTrue($this->fileStoreManager->exists($relativePath));

        $removedPath = $this->fileStoreManager->remove($relativePath);
        self::assertSame($expectedAbsolutePath, $removedPath);
        self::assertFalse($this->fileStoreManager->exists($relativePath));
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
        $this->fixtureCreator->copyFixtureSetTo($fixtureSet, $relativePath);

        $files = $this->fileStoreManager->list($relativePath, $extensions);
        self::assertCount(count($expectedRelativePathNames), $files);

        $iteratorIndex = 0;
        foreach ($files as $file) {
            self::assertInstanceOf(SplFileInfo::class, $file);
            self::assertSame($expectedRelativePathNames[$iteratorIndex], $file->getRelativePathname());
            ++$iteratorIndex;
        }
    }

    /**
     * @return array<mixed>
     */
    public function listDataProvider(): array
    {
        return [
            'source: txt, no extensions' => [
                'fixtureSet' => 'txt',
                'relativePath' => (string) new FileSource(UserId::create(), ''),
                'extensions' => [],
                'expectedRelativePathNames' => [
                    'directory/file3.txt',
                    'file1.txt',
                    'file2.txt',
                ],
            ],
            'source: txt, extensions=[txt]' => [
                'fixtureSet' => 'txt',
                'relativePath' => (string) new FileSource(UserId::create(), ''),
                'extensions' => ['txt'],
                'expected' => [
                    'directory/file3.txt',
                    'file1.txt',
                    'file2.txt',
                ],
            ],
            'source: txt, extensions=[yml, yaml]' => [
                'fixtureSet' => 'txt',
                'relativePath' => (string) new FileSource(UserId::create(), ''),
                'extensions' => ['yml', 'yaml'],
                'expected' => [],
            ],
            'source: yml_yaml, no extensions' => [
                'fixtureSet' => 'yml_yaml_valid',
                'relativePath' => (string) new FileSource(UserId::create(), ''),
                'extensions' => [],
                'expected' => [
                    'directory/file3.yml',
                    'file1.yaml',
                    'file2.yml',
                ],
            ],
            'source: yml_yaml, extensions=[yml]' => [
                'fixtureSet' => 'yml_yaml_valid',
                'relativePath' => (string) new FileSource(UserId::create(), ''),
                'extensions' => ['yml'],
                'expected' => [
                    'directory/file3.yml',
                    'file2.yml',
                ],
            ],
            'source: mixed, extensions=[ yaml]' => [
                'fixtureSet' => 'mixed',
                'relativePath' => (string) new FileSource(UserId::create(), ''),
                'extensions' => ['yaml'],
                'expected' => [
                    'directory/file3.yaml',
                    'file1.yaml',
                ],
            ],
        ];
    }

    /**
     * @dataProvider addDataProvider
     */
    public function testAdd(string $fileRelativePath, string $content): void
    {
        $this->fileStoreManager->add($fileRelativePath, $content);

        $expectedAbsolutePath = Path::canonicalize($this->createFileStoreAbsolutePath($fileRelativePath));

        self::assertFileExists($expectedAbsolutePath);
        self::assertSame($content, file_get_contents($expectedAbsolutePath));

        unlink($expectedAbsolutePath);
    }

    /**
     * @return array<mixed>
     */
    public function addDataProvider(): array
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

    private function createFileStoreAbsolutePath(string $relativePath): string
    {
        return Path::canonicalize($this->fileStoreBasePath . '/' . $relativePath);
    }
}
