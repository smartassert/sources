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
use App\Exception\GitActionException;
use App\Exception\ProcessExecutorException;
use App\Model\ProcessOutput;
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
     * @throws GitActionException
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

        $gitActionException = null;
        $gitAction = GitActionException::ACTION_CLONE;
        $cloneOutput = null;
        $checkoutOutput = null;

        try {
            $cloneOutput = $this->gitRepositoryCloner->clone(
                $this->createRepositoryUrl($source),
                $gitRepositoryAbsolutePath
            );

            if ($cloneOutput->isSuccessful()) {
                $gitAction = GitActionException::ACTION_CHECKOUT;
                $checkoutOutput = $this->gitRepositoryCheckoutHandler->checkout($gitRepositoryAbsolutePath, $ref);
            }
        } catch (ProcessExecutorException $processExecutorException) {
            $gitActionException = GitActionException::createForProcessException($gitAction, $processExecutorException);
        } finally {
            if ($cloneOutput instanceof ProcessOutput && false === $cloneOutput->isSuccessful()) {
                $gitActionException = GitActionException::createFromCloneOutput($cloneOutput->getErrorOutput());
            }

            if ($checkoutOutput instanceof ProcessOutput && false === $checkoutOutput->isSuccessful()) {
                $gitActionException = GitActionException::createFromCheckoutOutput($checkoutOutput->getErrorOutput());
            }

            if ($gitActionException instanceof GitActionException) {
                $this->fileStoreManager->remove($gitRepositoryRelativePath);

                throw $gitActionException;
            }
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
