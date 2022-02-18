<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\GitSource;
use App\Exception\GitActionException;
use App\Exception\GitRepositoryException;
use App\Model\ProcessOutput;
use App\Model\UserGitRepository;
use Symfony\Component\String\UnicodeString;

class GitRepositoryStore
{
    public function __construct(
        private FileStoreInterface $gitRepositoryFileStore,
        private PathFactory $gitRepositoryPathFactory,
        private GitRepositoryCloner $cloner,
        private GitRepositoryCheckoutHandler $checkoutHandler,
        private UserGitRepositoryFactory $gitRepositoryFactory,
    ) {
    }

    /**
     * @throws GitRepositoryException
     */
    public function initialize(GitSource $source, ?string $ref): UserGitRepository
    {
        $gitRepository = $this->gitRepositoryFactory->create($source);
        $relativePath = (string) $gitRepository;

        try {
            $this->gitRepositoryFileStore->remove($relativePath);
        } catch (\Throwable $throwable) {
            throw new GitRepositoryException($throwable);
        }

        $absolutePath = $this->gitRepositoryPathFactory->createAbsolutePath($relativePath);
        $gitRepositoryUrl = $this->createRepositoryUrl($gitRepository->getSource());

        try {
            $this->doCreate($gitRepositoryUrl, $absolutePath, $ref);
        } catch (\Throwable $throwable) {
            throw new GitRepositoryException($throwable);
        }

        return $gitRepository;
    }

    /**
     * @throws GitActionException
     */
    private function doCreate(string $gitRepositoryUrl, string $gitRepositoryAbsolutePath, ?string $ref): void
    {
        $gitActionException = null;
        $cloneOutput = null;
        $checkoutOutput = null;

        try {
            $cloneOutput = $this->cloner->clone($gitRepositoryUrl, $gitRepositoryAbsolutePath);

            if ($cloneOutput->isSuccessful()) {
                $checkoutOutput = $this->checkoutHandler->checkout($gitRepositoryAbsolutePath, $ref);
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
