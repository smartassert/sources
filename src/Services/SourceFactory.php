<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Source;
use App\Repository\SourceRepository;
use App\Request\CreateSourceRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Ulid;

class SourceFactory
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SourceRepository $repository,
    ) {
    }

    public function create(string $userId, string $hostUrl, string $path, ?string $accessToken): Source
    {
        $source = $this->repository->findOneBy([
            'userId' => $userId,
            'hostUrl' => $hostUrl,
            'path' => $path,
        ]);

        if ($source instanceof Source) {
            return $source;
        }

        $source = new Source((string) new Ulid(), $userId, $hostUrl, $path, $accessToken);

        $this->entityManager->persist($source);
        $this->entityManager->flush();

        return $source;
    }

    public function createFromRequest(CreateSourceRequest $request): Source
    {
        return $this->create(
            $request->getUserId(),
            $request->getHostUrl(),
            $request->getPath(),
            $request->getAccessToken()
        );
    }
}
