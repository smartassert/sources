<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\FileSource;
use App\Exception\EmptyEntityIdException;
use App\Repository\SourceRepository;
use App\Request\FileSourceRequest;
use App\Services\EntityIdFactory;
use SmartAssert\UsersSecurityBundle\Security\User;

class FileSourceFactory
{
    public function __construct(
        private readonly EntityIdFactory $entityIdFactory,
        private readonly SourceRepository $sourceRepository,
        private readonly FileSourceFinder $finder,
    ) {
    }

    /**
     * @throws EmptyEntityIdException
     */
    public function create(User $user, FileSourceRequest $request): FileSource
    {
        $source = $this->finder->find($user->getUserIdentifier(), $request->label);

        if (null === $source) {
            $source = new FileSource(
                $this->entityIdFactory->create(),
                $user->getUserIdentifier(),
            );

            $source->setLabel($request->label);

            $this->sourceRepository->save($source);
        }

        return $source;
    }
}
