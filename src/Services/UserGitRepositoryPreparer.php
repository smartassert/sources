<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\GitSource;
use App\Exception\File\RemoveException;
use App\Exception\GitActionException;
use App\Exception\UserGitRepositoryException;
use App\Model\ProcessOutput;
use App\Model\UserGitRepository;
use Symfony\Component\String\UnicodeString;

class UserGitRepositoryPreparer
{
    public function __construct(
        private FileStoreInterface $gitRepositoryFileStore,
        private GitRepositoryCloner $gitRepositoryCloner,
        private GitRepositoryCheckoutHandler $gitRepositoryCheckoutHandler,
        private PathFactory $gitRepositoryPathFactory,
    ) {
    }

    /**
     * @throws UserGitRepositoryException
     */
    public function prepare(GitSource $source, ?string $ref = null): UserGitRepository
    {
        $gitRepository = new UserGitRepository($source);

        try {
            $this->doPrepare($gitRepository, $ref);
        } catch (\Throwable $e) {
            throw new UserGitRepositoryException($gitRepository, $e);
        }

        return $gitRepository;
    }

    /**
     * @throws GitActionException
     * @throws RemoveException
     */
    private function doPrepare(UserGitRepository $gitRepository, ?string $ref = null): void
    {
        $gitRepositoryRelativePath = (string) $gitRepository;

        $this->gitRepositoryFileStore->remove($gitRepositoryRelativePath);

        $gitRepositoryAbsolutePath = $this->gitRepositoryPathFactory->createAbsolutePath($gitRepositoryRelativePath);

        $gitActionException = null;
        $cloneOutput = null;
        $checkoutOutput = null;

        try {
            $cloneOutput = $this->gitRepositoryCloner->clone(
                $this->createRepositoryUrl($gitRepository->getSource()),
                $gitRepositoryAbsolutePath
            );

            if ($cloneOutput->isSuccessful()) {
                $checkoutOutput = $this->gitRepositoryCheckoutHandler->checkout($gitRepositoryAbsolutePath, $ref);
            }
        } catch (GitActionException $gitActionException) {
        } finally {
            if ($cloneOutput instanceof ProcessOutput && false === $cloneOutput->isSuccessful()) {
                $gitActionException = GitActionException::createFromCloneOutput($cloneOutput->getErrorOutput());
            }

            if ($checkoutOutput instanceof ProcessOutput && false === $checkoutOutput->isSuccessful()) {
                $gitActionException = GitActionException::createFromCheckoutOutput($checkoutOutput->getErrorOutput());
            }

            if ($gitActionException instanceof GitActionException) {
                $this->gitRepositoryFileStore->remove($gitRepositoryRelativePath);

                throw $gitActionException;
            }
        }
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
