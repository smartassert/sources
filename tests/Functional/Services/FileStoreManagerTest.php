<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Model\AbsoluteFileLocator;
use App\Model\UserGitRepository;
use App\Services\FileStoreManager;
use App\Tests\Mock\Model\MockFileLocator;
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

    public function testExistsInitializeRemoveSuccess(): void
    {
        $fileLocator = (new MockFileLocator())->withToStringCall(UserId::create())->getMock();
        self::assertFalse($this->fileStoreManager->exists($fileLocator));

        $expectedFileStoreAbsolutePath = $this->createFileStoreAbsolutePath((string) $fileLocator);

        $initializedPath = $this->fileStoreManager->initialize($fileLocator);
        self::assertInstanceOf(AbsoluteFileLocator::class, $initializedPath);
        self::assertSame($expectedFileStoreAbsolutePath, (string) $initializedPath);
        self::assertTrue($this->fileStoreManager->exists($fileLocator));

        $removedPath = $this->fileStoreManager->remove($fileLocator);
        self::assertInstanceOf(AbsoluteFileLocator::class, $removedPath);
        self::assertSame($expectedFileStoreAbsolutePath, (string) $removedPath);
        self::assertFalse($this->fileStoreManager->exists($fileLocator));
    }

    public function testMirrorSuccess(): void
    {
        $userId = UserId::create();
        $gitSource = new GitSource($userId, 'https://example.com/repository.git');
        $sourceRelativeLocator = new UserGitRepository($gitSource);
        self::assertFalse($this->fileStoreManager->exists($sourceRelativeLocator));

        $sourceAbsoluteLocator = $this->fileStoreManager->initialize($sourceRelativeLocator);
        self::assertTrue($this->fileStoreManager->exists($sourceRelativeLocator));

        $this->fixtureCreator->copyFixturesTo((string) $sourceRelativeLocator);

        $targetFileLocator = new RunSource($gitSource);
        self::assertFalse($this->fileStoreManager->exists($targetFileLocator));

        $expectedTargetPath = $this->createFileStoreAbsolutePath((string) $targetFileLocator);
        $targetAbsoluteLocator = $this->fileStoreManager->mirror($sourceRelativeLocator, $targetFileLocator);
        self::assertInstanceOf(AbsoluteFileLocator::class, $targetAbsoluteLocator);
        self::assertSame($expectedTargetPath, (string) $targetAbsoluteLocator);
        self::assertSame(scandir((string) $sourceAbsoluteLocator), scandir($expectedTargetPath));
    }

    private function createFileStoreAbsolutePath(string $relativePath): string
    {
        return Path::canonicalize($this->fileStoreBasePath . '/' . $relativePath);
    }
}
