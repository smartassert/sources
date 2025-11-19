<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\GitSource;
use App\Exception\GitActionException;
use App\Exception\GitRepositoryException;
use App\Model\UserGitRepository;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemWriter;
use Symfony\Component\String\UnicodeString;

class GitRepositoryStore
{
    public function __construct(
        private FilesystemWriter $gitRepositoryWriter,
        private GitRepositoryCloner $cloner,
        private GitRepositoryCheckoutHandler $checkoutHandler,
        private UserGitRepositoryFactory $gitRepositoryFactory,
        private string $gitRepositoryBasePath,
    ) {}

    /**
     * @throws GitRepositoryException
     */
    public function initialize(GitSource $source, ?string $ref): UserGitRepository
    {
        $gitRepository = $this->gitRepositoryFactory->create($source);
        $relativePath = $gitRepository->getDirectoryPath();
        $absolutePath = $this->gitRepositoryBasePath . '/' . $relativePath;

        $gitRepositoryUrl = $this->createRepositoryUrl($gitRepository->getSource());

        try {
            $this->gitRepositoryWriter->deleteDirectory($relativePath);

            $cloneOutput = $this->cloner->clone($gitRepositoryUrl, $absolutePath);
            if (false === $cloneOutput->isSuccessful()) {
                throw GitActionException::createFromCloneOutput($cloneOutput->getErrorOutput());
            }

            $checkoutOutput = $this->checkoutHandler->checkout($absolutePath, $ref);
            if (false === $checkoutOutput->isSuccessful()) {
                throw GitActionException::createFromCheckoutOutput($checkoutOutput->getErrorOutput());
            }
        } catch (\Throwable $e) {
            throw new GitRepositoryException($e);
        }

        return $gitRepository;
    }

    /**
     * @throws FilesystemException
     */
    public function remove(UserGitRepository $gitRepository): void
    {
        $this->gitRepositoryWriter->deleteDirectory($gitRepository->getDirectoryPath());
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
