<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Entity\GitSource;
use App\Exception\File\OutOfScopeException;
use App\Exception\File\RemoveException;
use App\Exception\UserGitRepositoryException;
use App\Services\FileStoreManager;
use App\Services\GitRepositoryCheckoutHandler;
use App\Services\GitRepositoryCloner;
use App\Services\UserGitRepositoryPreparer;
use App\Tests\Mock\Services\MockFileStoreManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Exception\IOException;

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
        $removeException = new RemoveException('/path/to/remove', \Mockery::mock(IOException::class));
        $outOfScopeException = new OutOfScopeException('/path', '/base-path');

        return [
            'remove throws exception' => [
                'fileStoreManager' => (new MockFileStoreManager())
                    ->withRemoveCall($removeException)
                    ->getMock(),
                'expectedPrevious' => $removeException,
            ],
            'create throws exception' => [
                'fileStoreManager' => (new MockFileStoreManager())
                    ->withRemoveCall('/path')
                    ->withCreateCallThrowingException($outOfScopeException)
                    ->getMock(),
                'expectedPrevious' => $outOfScopeException,
            ],
        ];
    }
}
