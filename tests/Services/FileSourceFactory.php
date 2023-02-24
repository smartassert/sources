<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Entity\FileSource;
use App\Services\EntityIdFactory;
use App\Tests\Model\UserId;

class FileSourceFactory
{
    /**
     * @param null|non-empty-string $userId
     * @param null|non-empty-string $label
     * @param non-empty-string      $userId
     */
    public static function create(?string $userId = null, ?string $label = null): FileSource
    {
        $userId = is_string($userId) ? $userId : UserId::create();

        $source = new FileSource((new EntityIdFactory())->create(), $userId);

        $label = is_string($label) ? $label : md5((string) rand());
        $source->setLabel($label);

        return $source;
    }
}
