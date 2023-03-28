<?php

declare(strict_types=1);

namespace App\Tests\DataProvider;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Enum\Source\Type;
use App\Tests\Services\AuthenticationProvider\UserProvider;
use App\Tests\Services\SourceOriginFactory;

trait GetSourceDataProviderTrait
{
    /**
     * @return array<mixed>
     */
    public function getSourceDataProvider(): array
    {
        return [
            'git source with credentials' => [
                'sourceCreator' => function (UserProvider $users) {
                    return SourceOriginFactory::create(
                        type: 'git',
                        userId: $users->get(self::USER_1_EMAIL)->id,
                        credentials: md5((string) rand()),
                    );
                },
                'expectedResponseDataCreator' => function (GitSource $source) {
                    $data = [
                        'id' => $source->getId(),
                        'user_id' => $source->getUserId(),
                        'type' => Type::GIT->value,
                        'label' => $source->getLabel(),
                        'host_url' => $source->getHostUrl(),
                        'path' => $source->getPath(),
                        'has_credentials' => true,
                    ];

                    $deletedAt = $source->getDeletedAt();
                    if ($deletedAt instanceof \DateTimeInterface) {
                        $data['deleted_at'] = (int) $deletedAt->format('U');
                    }

                    return $data;
                },
            ],
            'git source without credentials' => [
                'sourceCreator' => function (UserProvider $users) {
                    return SourceOriginFactory::create(
                        type: 'git',
                        userId: $users->get(self::USER_1_EMAIL)->id,
                    );
                },
                'expectedResponseDataCreator' => function (GitSource $source) {
                    $data = [
                        'id' => $source->getId(),
                        'user_id' => $source->getUserId(),
                        'type' => Type::GIT->value,
                        'label' => $source->getLabel(),
                        'host_url' => $source->getHostUrl(),
                        'path' => $source->getPath(),
                        'has_credentials' => false,
                    ];

                    $deletedAt = $source->getDeletedAt();
                    if ($deletedAt instanceof \DateTimeInterface) {
                        $data['deleted_at'] = (int) $deletedAt->format('U');
                    }

                    return $data;
                },
            ],
            'file' => [
                'sourceCreator' => function (UserProvider $users) {
                    return SourceOriginFactory::create(
                        type: 'file',
                        userId: $users->get(self::USER_1_EMAIL)->id,
                    );
                },
                'expectedResponseDataCreator' => function (FileSource $source) {
                    $data = [
                        'id' => $source->getId(),
                        'user_id' => $source->getUserId(),
                        'type' => Type::FILE->value,
                        'label' => $source->getLabel(),
                    ];

                    $deletedAt = $source->getDeletedAt();
                    if ($deletedAt instanceof \DateTimeInterface) {
                        $data['deleted_at'] = (int) $deletedAt->format('U');
                    }

                    return $data;
                },
            ],
        ];
    }
}
