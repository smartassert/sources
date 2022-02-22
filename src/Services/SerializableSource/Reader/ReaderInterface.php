<?php

declare(strict_types=1);

namespace App\Services\SerializableSource\Reader;

use App\Model\SerializableSourceInterface;
use League\Flysystem\FilesystemReader;

interface ReaderInterface
{
    public function handles(SerializableSourceInterface $serializableSource): bool;

    public function getReader(): FilesystemReader;
}
