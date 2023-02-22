<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\GitSource;
use App\Repository\GitSourceRepository;

class GitSourceFinder
{
    public function __construct(
        private readonly GitSourceRepository $repository,
    ) {
    }

    public function find(string $userId, string $label): ?GitSource
    {
        return $this->repository->findOneBy($this->createFindCriteria($userId, $label));
    }

    public function has(string $userId, string $label): bool
    {
        return $this->repository->count($this->createFindCriteria($userId, $label)) > 0;
    }

    /**
     * @return array{userId: string, label: string, deletedAt: null}
     */
    private function createFindCriteria(string $userId, string $label): array
    {
        return [
            'userId' => $userId,
            'label' => $label,
            'deletedAt' => null,
        ];
    }
}
