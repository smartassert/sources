<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Services\EntityIdFactory;
use App\Tests\Model\UserId;

class SourceOriginFactory
{
    /**
     * @param null|non-empty-string $userId
     * @param null|non-empty-string $label
     * @param null|non-empty-string $hostUrl
     * @param null|non-empty-string $path
     * @param non-empty-string      $userId
     */
    public static function create(
        string $type,
        ?string $userId = null,
        ?string $label = null,
        ?string $hostUrl = null,
        ?string $path = null,
        ?string $credentials = null,
    ): FileSource|GitSource {
        $userId = is_string($userId) ? $userId : UserId::create();

        if ('file' === $type) {
            $source = new FileSource((new EntityIdFactory())->create(), $userId);
        } else {
            $source = new GitSource((new EntityIdFactory())->create(), $userId);
        }

        $label = is_string($label) ? $label : md5((string) rand());
        $source->setLabel($label);

        if ($source instanceof GitSource) {
            $hostUrl = is_string($hostUrl) ? $hostUrl : 'https://example.com/' . md5((string) rand()) . '.git';
            $source->setHostUrl($hostUrl);

            $path = is_string($path) ? $path : '/';
            $source->setPath($path);

            $credentials = is_string($credentials) ? $credentials : '';
            $source->setCredentials($credentials);
        }

        return $source;
    }
}
