<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Repository\FileSourceRepository;
use App\Repository\GitSourceRepository;
use App\Repository\RunSourceRepository;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Ulid;

class Factory
{
    public function __construct(
        private Store $store,
        private GitSourceRepository $gitSourceRepository,
        private FileSourceRepository $fileSourceRepository,
        private RunSourceRepository $runSourceRepository,
    ) {
    }

    /**
     * @param array<string, string> $parameters
     */
    public function createRunSource(FileSource|GitSource $parent, array $parameters = []): RunSource
    {
        ksort($parameters);

        $source = $this->runSourceRepository->findOneBy([
            'parent' => $parent,
            'parameters' => $parameters,
        ]);

        if ($source instanceof RunSource) {
            return $source;
        }

        return new RunSource($this->generateId(), $parent, $parameters);
    }

    public function createGitSourceFromRequest(UserInterface $user, GitSourceRequest $request): GitSource
    {
        $source = $this->createGitSource(
            $user->getUserIdentifier(),
            $request->getHostUrl(),
            $request->getPath(),
            $request->getAccessToken()
        );

        $this->store->add($source);

        return $source;
    }

    public function createFileSourceFromRequest(UserInterface $user, FileSourceRequest $request): FileSource
    {
        $source = $this->createFileSource($user->getUserIdentifier(), $request->getLabel());
        $this->store->add($source);

        return $source;
    }

    private function generateId(): string
    {
        return (string) new Ulid();
    }

    private function createGitSource(string $userId, string $hostUrl, string $path, ?string $accessToken): GitSource
    {
        $source = $this->gitSourceRepository->findOneBy([
            'userId' => $userId,
            'hostUrl' => $hostUrl,
            'path' => $path,
        ]);

        if ($source instanceof GitSource) {
            return $source;
        }

        return new GitSource($this->generateId(), $userId, $hostUrl, $path, $accessToken);
    }

    private function createFileSource(string $userId, string $label): FileSource
    {
        $source = $this->fileSourceRepository->findOneBy([
            'userId' => $userId,
            'label' => $label,
        ]);

        if ($source instanceof FileSource) {
            return $source;
        }

        return new FileSource($this->generateId(), $userId, $label);
    }
}
