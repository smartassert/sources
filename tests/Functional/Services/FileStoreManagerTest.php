<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Model\UserGitRepository;
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

    public function testMirrorSuccess(): void
    {
        $userId = UserId::create();
        $gitSource = new GitSource($userId, 'https://example.com/repository.git');
        $sourceRelativePath = (string) (new UserGitRepository($gitSource));
        self::assertFalse($this->fileStoreManager->exists($sourceRelativePath));

        $sourceAbsolutePath = $this->fileStoreManager->create($sourceRelativePath);
        self::assertTrue($this->fileStoreManager->exists($sourceRelativePath));

        $this->fixtureCreator->copyFixtureSetTo('source_txt', $sourceRelativePath);

        $targetRelativePath = (string) (new RunSource($gitSource));
        self::assertFalse($this->fileStoreManager->exists($targetRelativePath));

        $expectedTargetPath = $this->createFileStoreAbsolutePath($targetRelativePath);
        $targetAbsolutePath = $this->fileStoreManager->mirror($sourceRelativePath, $targetRelativePath);
        self::assertSame($expectedTargetPath, $targetAbsolutePath);
        self::assertSame(scandir($sourceAbsolutePath), scandir($expectedTargetPath));
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
            'source_txt, no extensions' => [
                'fixtureSet' => 'source_txt',
                'relativePath' => (string) new FileSource(UserId::create(), ''),
                'extensions' => [],
                'expectedRelativePathNames' => [
                    'directory/file3.txt',
                    'file1.txt',
                    'file2.txt',
                ],
            ],
            'source_txt, extensions=[txt]' => [
                'fixtureSet' => 'source_txt',
                'relativePath' => (string) new FileSource(UserId::create(), ''),
                'extensions' => ['txt'],
                'expected' => [
                    'directory/file3.txt',
                    'file1.txt',
                    'file2.txt',
                ],
            ],
            'source_txt, extensions=[yml, yaml]' => [
                'fixtureSet' => 'source_txt',
                'relativePath' => (string) new FileSource(UserId::create(), ''),
                'extensions' => ['yml', 'yaml'],
                'expected' => [],
            ],
            'source_yml_yaml, no extensions' => [
                'fixtureSet' => 'source_yml_yaml',
                'relativePath' => (string) new FileSource(UserId::create(), ''),
                'extensions' => [],
                'expected' => [
                    'directory/file3.yml',
                    'file1.yaml',
                    'file2.yml',
                ],
            ],
            'source_yml_yaml, extensions=[yml]' => [
                'fixtureSet' => 'source_yml_yaml',
                'relativePath' => (string) new FileSource(UserId::create(), ''),
                'extensions' => ['yml'],
                'expected' => [
                    'directory/file3.yml',
                    'file2.yml',
                ],
            ],
            'source_mixed, extensions=[ yaml]' => [
                'fixtureSet' => 'source_mixed',
                'relativePath' => (string) new FileSource(UserId::create(), ''),
                'extensions' => ['yaml'],
                'expected' => [
                    'directory/file3.yaml',
                    'file1.yaml',
                ],
            ],
        ];
    }

    private function createFileStoreAbsolutePath(string $relativePath): string
    {
        return Path::canonicalize($this->fileStoreBasePath . '/' . $relativePath);
    }
}
