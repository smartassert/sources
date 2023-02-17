<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\FileSource;
use App\Exception\EmptyEntityIdException;
use App\Repository\FileSourceRepository;
use App\Repository\SourceRepository;
use App\Request\FileSourceRequest;
use App\Services\EntityIdFactory;
use SmartAssert\UsersSecurityBundle\Security\User;

class FileSourceFactory
{
    public function __construct(
        private readonly EntityIdFactory $entityIdFactory,
        private readonly FileSourceRepository $fileSourceRepository,
        private readonly SourceRepository $sourceRepository,
    ) {
    }

    /**
     * @throws EmptyEntityIdException
     */
    public function create(User $user, FileSourceRequest $request): FileSource
    {
        $source = $this->fileSourceRepository->findOneBy([
            'userId' => $user->getUserIdentifier(),
            'label' => $request->label,
            'deletedAt' => null,
        ]);

        if (null === $source) {
            $source = new FileSource(
                $this->entityIdFactory->create(),
                $user->getUserIdentifier(),
                $request->label
            );

            $this->sourceRepository->save($source);
        }

        return $source;
    }
}
