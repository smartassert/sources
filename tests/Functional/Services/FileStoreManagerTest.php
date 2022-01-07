<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Model\UserGitRepository;
use App\Services\FileStoreManager;
use App\Tests\Mock\Model\MockFileLocator;
use App\Tests\Model\UserId;
use App\Tests\Services\FileStoreFixtureCreator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FileStoreManagerTest extends WebTestCase
{
    private FileStoreManager $fileStoreManager;
    private FileStoreFixtureCreator $fixtureCreator;

    protected function setUp(): void
    {
        parent::setUp();

        $fileStoreManager = self::getContainer()->get(FileStoreManager::class);
        \assert($fileStoreManager instanceof FileStoreManager);
        $this->fileStoreManager = $fileStoreManager;

        $fixtureCreator = self::getContainer()->get(FileStoreFixtureCreator::class);
        \assert($fixtureCreator instanceof FileStoreFixtureCreator);
        $this->fixtureCreator = $fixtureCreator;
    }

    public function testExistsInitializeRemoveSuccess(): void
    {
        $fileLocator = (new MockFileLocator())->withToStringCall(UserId::create())->getMock();
        self::assertFalse($this->fileStoreManager->exists($fileLocator));

        $this->fileStoreManager->initialize($fileLocator);
        self::assertTrue($this->fileStoreManager->exists($fileLocator));

        $this->fileStoreManager->remove($fileLocator);
        self::assertFalse($this->fileStoreManager->exists($fileLocator));
    }

    public function testMirrorSuccess(): void
    {
        $userId = UserId::create();
        $gitSource = new GitSource($userId, 'https://example.com/repository.git');
        $sourceFileLocator = new UserGitRepository($gitSource);
        self::assertFalse($this->fileStoreManager->exists($sourceFileLocator));

        $this->fileStoreManager->initialize($sourceFileLocator);
        self::assertTrue($this->fileStoreManager->exists($sourceFileLocator));

        $this->fixtureCreator->copyFixturesTo((string) $sourceFileLocator);

        $targetFileLocator = new RunSource($gitSource);
        self::assertFalse($this->fileStoreManager->exists($targetFileLocator));

        $this->fileStoreManager->mirror($sourceFileLocator, $targetFileLocator);

        self::assertSame(
            scandir($this->fileStoreManager->createPath($sourceFileLocator)),
            scandir($this->fileStoreManager->createPath($targetFileLocator))
        );
    }
}
