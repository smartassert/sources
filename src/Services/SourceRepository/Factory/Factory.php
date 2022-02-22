<?php

declare(strict_types=1);

namespace App\Services\SourceRepository\Factory;

use App\Entity\SourceOriginInterface;
use App\Exception\SourceRepositoryCreationException;
use App\Model\SourceRepositoryInterface;
use League\Flysystem\FilesystemException;

class Factory implements CreatorInterface, DestructorInterface
{
    /**
     * @param object[] $handlers
     */
    public function __construct(
        private array $handlers,
    ) {
    }

    public function createsFor(SourceOriginInterface $source): bool
    {
        return $this->findCreator($source) instanceof CreatorInterface;
    }

    /**
     * @throws SourceRepositoryCreationException
     */
    public function create(SourceOriginInterface $source, array $parameters): ?SourceRepositoryInterface
    {
        $creator = $this->findCreator($source);

        return $creator instanceof CreatorInterface
            ? $creator->create($source, $parameters)
            : null;
    }

    public function removes(SourceRepositoryInterface $sourceRepository): bool
    {
        return $this->findDestructor($sourceRepository) instanceof DestructorInterface;
    }

    /**
     * @throws FilesystemException
     */
    public function remove(SourceRepositoryInterface $sourceRepository): void
    {
        $destructor = $this->findDestructor($sourceRepository);

        if ($destructor instanceof DestructorInterface) {
            $destructor->remove($sourceRepository);
        }
    }

    private function findCreator(SourceOriginInterface $source): ?CreatorInterface
    {
        foreach ($this->handlers as $handler) {
            if (
                $handler instanceof CreatorInterface
                && $handler->createsFor($source)
            ) {
                return $handler;
            }
        }

        return null;
    }

    private function findDestructor(SourceRepositoryInterface $sourceRepository): ?DestructorInterface
    {
        foreach ($this->handlers as $handler) {
            if (
                $handler instanceof DestructorInterface
                && $handler->removes($sourceRepository)
            ) {
                return $handler;
            }
        }

        return null;
    }
}
