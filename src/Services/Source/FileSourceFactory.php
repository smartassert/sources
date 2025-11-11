<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\FileSource;
use App\Request\FileSourceRequest;
use App\Services\EntityIdFactory;
use SmartAssert\ServiceRequest\Exception\ErrorResponseException;
use SmartAssert\UsersSecurityBundle\Security\User;

readonly class FileSourceFactory
{
    public function __construct(
        private EntityIdFactory $entityIdFactory,
        private Mutator $sourceMutator,
    ) {
    }

    /**
     * @throws ErrorResponseException
     */
    public function create(User $user, FileSourceRequest $request): FileSource
    {
        return $this->sourceMutator->updateFile(
            new FileSource($this->entityIdFactory->create(), $user->getUserIdentifier()),
            $request
        );
    }
}
