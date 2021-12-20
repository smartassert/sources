<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\AbstractSource;
use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Repository\FileSourceRepository;
use App\Repository\GitSourceRepository;
use App\Repository\RunSourceRepository;
use App\Request\CreateSourceRequest;
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

    public function createRunSource(string $userId, FileSource|GitSource $parent): RunSource
    {
        $source = $this->runSourceRepository->findOneBy([
            'parent' => $parent,
        ]);

        if ($source instanceof RunSource) {
            return $source;
        }

        $source = new RunSource($this->generateId(), $userId, $parent);
        $this->persist($source);

        return $source;
    }

    public function createFromRequest(UserInterface $user, CreateSourceRequest $request): GitSource
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

    private function persist(AbstractSource $source): void
    {
        $this->entityManager->persist($source);
        $this->entityManager->flush();
    }
}
