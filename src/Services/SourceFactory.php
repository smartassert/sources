<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Repository\FileSourceRepository;
use App\Repository\GitSourceRepository;
use App\Repository\RunSourceRepository;
use App\Request\CreateGitSourceRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Ulid;

class SourceFactory
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private GitSourceRepository $gitSourceRepository,
        private FileSourceRepository $fileSourceRepository,
        private RunSourceRepository $runSourceRepository,
    ) {
    }

    public function createGitSource(string $userId, string $hostUrl, string $path, ?string $accessToken): GitSource
    {
        $source = $this->gitSourceRepository->findOneBy([
            'userId' => $userId,
            'hostUrl' => $hostUrl,
            'path' => $path,
        ]);

        if ($source instanceof GitSource) {
            return $source;
        }

        $source = new GitSource($this->generateId(), $userId, $hostUrl, $path, $accessToken);
        $this->persist($source);

        return $source;
    }

    public function createFileSource(string $userId, string $label): FileSource
    {
        $source = $this->fileSourceRepository->findOneBy([
            'userId' => $userId,
            'label' => $label,
        ]);

        if ($source instanceof FileSource) {
            return $source;
        }

        $source = new FileSource($this->generateId(), $userId, $label);
        $this->persist($source);

        return $source;
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

        $source = new RunSource($this->generateId(), $parent, $parameters);
        $this->persist($source);

        return $source;
    }

    public function createGitSourceFromRequest(UserInterface $user, CreateGitSourceRequest $request): GitSource
    {
        return $this->createGitSource(
            $user->getUserIdentifier(),
            $request->getHostUrl(),
            $request->getPath(),
            $request->getAccessToken()
        );
    }

    private function generateId(): string
    {
        return (string) new Ulid();
    }

    private function persist(SourceInterface $source): void
    {
        $this->entityManager->persist($source);
        $this->entityManager->flush();
    }
}
