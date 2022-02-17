<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Entity\GitSource;
use App\Exception\File\RemoveException;
use App\Exception\UserGitRepositoryException;
use App\Services\FileStoreManager;
use App\Services\GitRepositoryCheckoutHandler;
use App\Services\GitRepositoryCloner;
use App\Services\PathFactory;
use App\Services\UserGitRepositoryPreparer;
use League\Flysystem\UnableToDeleteDirectory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserGitRepositoryPreparerTest extends WebTestCase
{
    /**
     * @dataProvider prepareFileStoreManagerThrowsExceptionDataProvider
     */
    public function testPrepareFileStoreManagerThrowsException(
        FileStoreManager $fileStoreManager,
        \Exception $expectedPrevious
    ): void {
        $preparer = new UserGitRepositoryPreparer(
            $fileStoreManager,
            \Mockery::mock(GitRepositoryCloner::class),
            \Mockery::mock(GitRepositoryCheckoutHandler::class),
            \Mockery::mock(PathFactory::class),
        );

        try {
            $preparer->prepare(new GitSource('user-id', 'host-url'));
            $this->fail(UserGitRepositoryException::class . ' not thrown');
        } catch (UserGitRepositoryException $userGitRepositoryException) {
            $previous = $userGitRepositoryException->getPrevious();
            self::assertSame($expectedPrevious, $previous);
            self::assertSame($expectedPrevious->getMessage(), $userGitRepositoryException->getMessage());
        }
    }

    /**
     * @return array<mixed>
     */
    public function prepareFileStoreManagerThrowsExceptionDataProvider(): array
    {
        $unableToDeleteDirectoryException = UnableToDeleteDirectory::atLocation('/path/to/remove');

        $removeException = new RemoveException('/path/to/remove', $unableToDeleteDirectoryException);

        $fileStoreManagerThrowingRemoveException = \Mockery::mock(FileStoreManager::class);
        $fileStoreManagerThrowingRemoveException
            ->shouldReceive('remove')
            ->andThrow($removeException)
        ;

        return [
            'remove throws exception' => [
                'fileStoreManager' => $fileStoreManagerThrowingRemoveException,
                'expectedPrevious' => $removeException,
            ],
        ];
    }
}
