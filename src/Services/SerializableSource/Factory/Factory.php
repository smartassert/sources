<?php

declare(strict_types=1);

namespace App\Services\SerializableSource\Factory;

use App\Entity\OriginSourceInterface;
use App\Exception\SerializableSourceCreationException;
use App\Model\SerializableSourceInterface;
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

    public function createsFor(OriginSourceInterface $origin): bool
    {
        return $this->findCreator($origin) instanceof CreatorInterface;
    }

    /**
     * @throws SerializableSourceCreationException
     */
    public function create(OriginSourceInterface $origin, array $parameters): ?SerializableSourceInterface
    {
        $creator = $this->findCreator($origin);

        return $creator instanceof CreatorInterface
            ? $creator->create($origin, $parameters)
            : null;
    }

    public function removes(SerializableSourceInterface $serializableSource): bool
    {
        return $this->findDestructor($serializableSource) instanceof DestructorInterface;
    }

    /**
     * @throws FilesystemException
     */
    public function remove(SerializableSourceInterface $serializableSource): void
    {
        $destructor = $this->findDestructor($serializableSource);

        if ($destructor instanceof DestructorInterface) {
            $destructor->remove($serializableSource);
        }
    }

    private function findCreator(OriginSourceInterface $origin): ?CreatorInterface
    {
        foreach ($this->handlers as $handler) {
            if (
                $handler instanceof CreatorInterface
                && $handler->createsFor($origin)
            ) {
                return $handler;
            }
        }

        return null;
    }

    private function findDestructor(SerializableSourceInterface $serializableSource): ?DestructorInterface
    {
        foreach ($this->handlers as $handler) {
            if (
                $handler instanceof DestructorInterface
                && $handler->removes($serializableSource)
            ) {
                return $handler;
            }
        }

        return null;
    }
}
