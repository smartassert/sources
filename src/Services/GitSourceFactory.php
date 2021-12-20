<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\GitSource;
use App\Repository\GitSourceRepository;
use App\Request\CreateSourceRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Ulid;

class GitSourceFactory
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private GitSourceRepository $repository,
    ) {
    }

    public function create(
        string $userId,
        string $hostUrl,
        string $path,
        ?string $accessToken
    ): GitSource {
        $source = $this->repository->findOneBy([
            'userId' => $userId,
            'hostUrl' => $hostUrl,
            'path' => $path,
        ]);

        if ($source instanceof GitSource) {
            return $source;
        }

        $source = new GitSource(
            (string) new Ulid(),
            $userId,
            $hostUrl,
            $path,
            $accessToken
        );

        $this->entityManager->persist($source);
        $this->entityManager->flush();

        return $source;
    }

    public function createFromRequest(UserInterface $user, CreateSourceRequest $request): GitSource
    {
        return $this->create(
            $user->getUserIdentifier(),
            $request->getHostUrl(),
            $request->getPath(),
            $request->getAccessToken()
        );
    }
}
