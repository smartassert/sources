<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Repository\FileSourceRepository;
use App\Repository\GitSourceRepository;

class OriginSourceFinder
{
    public function find(
        FileSourceRepository|GitSourceRepository $repository,
        string $userId,
        string $label
    ): null|FileSource|GitSource {
        return $repository->findOneBy($this->createFindCriteria($userId, $label));
    }

    public function has(FileSourceRepository|GitSourceRepository $repository, string $userId, string $label): bool
    {
        return $repository->count($this->createFindCriteria($userId, $label)) > 0;
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
