<?php

declare(strict_types=1);

namespace App\Services\SourceRepository\Factory;

use App\Entity\SourceInterface;
use App\Exception\NoSourceRepositoryCreatorException;
use App\Exception\SourceRepositoryCreationException;
use App\Model\SourceRepositoryInterface;
use League\Flysystem\FilesystemException;

class Factory
{
    /**
     * @param object[] $handlers
     */
    public function __construct(
        private readonly array $handlers,
    ) {}

    /**
     * @param array<string, string> $parameters
     *
     * @throws SourceRepositoryCreationException
     * @throws NoSourceRepositoryCreatorException
     */
    public function create(SourceInterface $source, array $parameters): SourceRepositoryInterface
    {
        foreach ($this->handlers as $handler) {
            if ($handler instanceof CreatorInterface && $handler->createsFor($source)) {
                $sourceRepository = $handler->create($source, $parameters);

                if ($sourceRepository instanceof SourceRepositoryInterface) {
                    return $sourceRepository;
                }
            }
        }

        throw new NoSourceRepositoryCreatorException($source);
    }

    /**
     * @throws FilesystemException
     */
    public function remove(SourceRepositoryInterface $sourceRepository): void
    {
        foreach ($this->handlers as $handler) {
            if ($handler instanceof DestructorInterface && $handler->removes($sourceRepository)) {
                $handler->remove($sourceRepository);
            }
        }
    }
}
