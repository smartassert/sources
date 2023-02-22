<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\FileSource;
use App\Repository\FileSourceRepository;

class FileSourceFinder
{
    public function __construct(
        private readonly FileSourceRepository $fileSourceRepository,
    ) {
    }

    public function find(string $userId, string $label): ?FileSource
    {
        return $this->fileSourceRepository->findOneBy($this->createFindCriteria($userId, $label));
    }

    public function has(string $userId, string $label): bool
    {
        return $this->fileSourceRepository->count($this->createFindCriteria($userId, $label)) > 0;
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
