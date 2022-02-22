<?php

declare(strict_types=1);

namespace App\Services\SerializableSource\Factory;

use App\Model\SerializableSourceInterface;
use League\Flysystem\FilesystemException;

interface DestructorInterface
{
    public function removes(SerializableSourceInterface $serializableSource): bool;

    /**
     * @throws FilesystemException
     */
    public function remove(SerializableSourceInterface $serializableSource): void;
}
