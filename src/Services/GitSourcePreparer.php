<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Exception\File\CreateException;
use App\Exception\File\MirrorException;
use App\Exception\File\NotExistsException;
use App\Exception\File\OutOfScopeException;
use App\Exception\File\RemoveException;
use App\Exception\GitCloneException;
use App\Exception\ProcessExecutorException;
use App\Model\UserGitRepository;
use App\Services\Source\Factory;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\String\UnicodeString;

class GitSourcePreparer
{
    public function __construct(
        private Factory $sourceFactory,
        private FileStoreManager $fileStoreManager,
        private GitRepositoryCloner $gitRepositoryCloner,
        private GitRepositoryCheckoutHandler $gitRepositoryCheckoutHandler,
    ) {
    }

    /**
     * @throws CreateException
     * @throws OutOfScopeException
     * @throws RemoveException
     * @throws MirrorException
     * @throws NotExistsException
     * @throws ProcessExecutorException
     * @throws GitCloneException
     */
    public function prepare(GitSource $source, ?string $ref = null): RunSource
    {
        $gitRepository = new UserGitRepository($source);
        $gitRepositoryRelativePath = (string) $gitRepository;

        try {
            $gitRepositoryAbsolutePath = $this->fileStoreManager->remove($gitRepositoryRelativePath);
            $this->fileStoreManager->create($gitRepositoryRelativePath);
        } catch (CreateException | OutOfScopeException $exception) {
            throw $exception->withContext('source');
        }

        $gitCloneException = null;
        try {
            $cloneProcessOutput = $this->gitRepositoryCloner->clone(
                $this->createRepositoryUrl($source),
                $gitRepositoryAbsolutePath
            );

            if (false === $cloneProcessOutput->isSuccessful()) {
                $gitCloneException = GitCloneException::createFromErrorOutput($cloneProcessOutput->getErrorOutput());
            }
        } catch (ProcessExecutorException $gitCloneProcessExecutorException) {
            $gitCloneException = new GitCloneException('Git clone process failed', $gitCloneProcessExecutorException);
        } finally {
            if ($gitCloneException instanceof GitCloneException) {
                $this->fileStoreManager->remove($gitRepositoryRelativePath);
                throw $gitCloneException;
            }
        }

        $checkoutProcessOutput = $this->gitRepositoryCheckoutHandler->checkout($gitRepositoryAbsolutePath, $ref);
        if (false === $checkoutProcessOutput->isSuccessful()) {
            // throw GitCheckoutException
            throw new \RuntimeException('foo!');
        }

        $runSource = $this->sourceFactory->createRunSource($source);
        $copyableSourcePath = Path::canonicalize($gitRepository->getPath() . '/' . $source->getPath());

        try {
            $this->fileStoreManager->mirror($copyableSourcePath, (string) $runSource);
        } catch (CreateException | NotExistsException | RemoveException $exception) {
            $this->fileStoreManager->remove($gitRepositoryRelativePath);

            throw $exception->withContext('target');
        } catch (MirrorException $mirrorException) {
            $this->fileStoreManager->remove($gitRepositoryRelativePath);

            throw $mirrorException;
        }

        $this->fileStoreManager->remove($gitRepositoryRelativePath);

        return $runSource;
    }

    private function createRepositoryUrl(GitSource $source): string
    {
        $url = $source->getHostUrl();

        $credentials = $source->getCredentials();
        if ('' === $credentials) {
            return $url;
        }

        $urlString = new UnicodeString($url);
        $urlString = $urlString->trimPrefix(['https://', 'http://']);

        return sprintf('%s%s@%s', 'https://', $credentials, $urlString);
    }
}
