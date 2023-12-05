<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\FileSource;
use App\Exception\DuplicateObjectException;
use App\Exception\EmptyEntityIdException;
use App\Request\FileSourceRequest;
use App\Services\EntityIdFactory;
use SmartAssert\UsersSecurityBundle\Security\User;

class FileSourceFactory
{
    public function __construct(
        private readonly EntityIdFactory $entityIdFactory,
        private readonly Mutator $sourceMutator,
    ) {
    }

    /**
     * @throws EmptyEntityIdException
     * @throws DuplicateObjectException
     */
    public function create(User $user, FileSourceRequest $request): FileSource
    {
        return $this->sourceMutator->updateFile(
            new FileSource($this->entityIdFactory->create(), $user->getUserIdentifier()),
            $request
        );
    }
}
