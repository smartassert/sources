<?php

declare(strict_types=1);

namespace App\Tests\DataProvider;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Enum\RunSource\FailureReason;
use App\Enum\RunSource\State;
use App\Enum\Source\Type;
use App\Services\EntityIdFactory;
use App\Tests\Services\AuthenticationConfiguration;
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
                'sourceCreator' => function (AuthenticationConfiguration $authenticationConfiguration) {
                    return SourceOriginFactory::create(
                        type: 'git',
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                        credentials: md5((string) rand()),
                    );
                },
                'expectedResponseDataCreator' => function (GitSource $source) {
                    return [
                        'id' => $source->getId(),
                        'user_id' => $source->getUserId(),
                        'type' => Type::GIT->value,
                        'label' => $source->getLabel(),
                        'host_url' => $source->getHostUrl(),
                        'path' => $source->getPath(),
                        'has_credentials' => true,
                    ];
                },
            ],
            'git source without credentials' => [
                'sourceCreator' => function (AuthenticationConfiguration $authenticationConfiguration) {
                    return SourceOriginFactory::create(
                        type: 'git',
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                    );
                },
                'expectedResponseDataCreator' => function (GitSource $source) {
                    return [
                        'id' => $source->getId(),
                        'user_id' => $source->getUserId(),
                        'type' => Type::GIT->value,
                        'label' => $source->getLabel(),
                        'host_url' => $source->getHostUrl(),
                        'path' => $source->getPath(),
                        'has_credentials' => false,
                    ];
                },
            ],
            'file' => [
                'sourceCreator' => function (AuthenticationConfiguration $authenticationConfiguration) {
                    return SourceOriginFactory::create(
                        type: 'file',
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                    );
                },
                'expectedResponseDataCreator' => function (FileSource $source) {
                    return [
                        'id' => $source->getId(),
                        'user_id' => $source->getUserId(),
                        'type' => Type::FILE->value,
                        'label' => $source->getLabel(),
                    ];
                },
            ],
            'run with file parent' => [
                'sourceCreator' => function (AuthenticationConfiguration $authenticationConfiguration) {
                    $parent = SourceOriginFactory::create(
                        type: 'file',
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id
                    );

                    return new RunSource((new EntityIdFactory())->create(), $parent);
                },
                'expectedResponseDataCreator' => function (RunSource $source) {
                    return [
                        'id' => $source->getId(),
                        'user_id' => $source->getUserId(),
                        'type' => Type::RUN->value,
                        'parent' => $source->getParent()->getId(),
                        'parameters' => [],
                        'state' => State::REQUESTED->value,
                    ];
                },
            ],
            'run with git parent' => [
                'sourceCreator' => function (AuthenticationConfiguration $authenticationConfiguration) {
                    $parent = SourceOriginFactory::create(
                        type: 'git',
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id
                    );

                    return new RunSource((new EntityIdFactory())->create(), $parent);
                },
                'expectedResponseDataCreator' => function (RunSource $source) {
                    return [
                        'id' => $source->getId(),
                        'user_id' => $source->getUserId(),
                        'type' => Type::RUN->value,
                        'parent' => $source->getParent()->getId(),
                        'parameters' => [],
                        'state' => State::REQUESTED->value,
                    ];
                },
            ],
            'run, preparation failed' => [
                'sourceCreator' => function (AuthenticationConfiguration $authenticationConfiguration) {
                    $parent = SourceOriginFactory::create(
                        type: 'file',
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id
                    );

                    $source = new RunSource((new EntityIdFactory())->create(), $parent);
                    $source->setPreparationFailed(
                        FailureReason::GIT_CLONE,
                        'fatal: repository \'http://example.com/with-credentials.git\' not found'
                    );

                    return $source;
                },
                'expectedResponseDataCreator' => function (RunSource $source) {
                    return [
                        'id' => $source->getId(),
                        'user_id' => $source->getUserId(),
                        'type' => Type::RUN->value,
                        'parent' => $source->getParent()->getId(),
                        'parameters' => [],
                        'state' => State::FAILED->value,
                        'failure_reason' => FailureReason::GIT_CLONE->value,
                        'failure_message' => 'fatal: repository \'http://example.com/with-credentials.git\' not found',
                    ];
                },
            ],
        ];
    }
}
