<?php

declare(strict_types=1);

namespace App\Exception;

use App\Entity\IdentifyingEntityInterface;
use League\Flysystem\FilesystemException;

class EntityStorageException extends \Exception
{
    public function __construct(
        public readonly IdentifyingEntityInterface $entity,
        public readonly FilesystemException $filesystemException
    ) {
        $message = sprintf(
            'Filesystem %s error for %s %s',
            'foo',
            $entity->getEntityType()->value,
            $entity->getId(),
        );

        parent::__construct($message, 0, $filesystemException);
    }
}
