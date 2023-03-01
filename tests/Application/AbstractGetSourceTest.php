<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Enum\RunSource\FailureReason;
use App\Enum\RunSource\State;
use App\Enum\Source\Type;
use App\Repository\SourceRepository;
use App\Services\EntityIdFactory;
use App\Tests\Services\AuthenticationConfiguration;
use App\Tests\Services\FileSourceFactory;
use App\Tests\Services\GitSourceFactory;

abstract class AbstractGetSourceTest extends AbstractApplicationTest
{
    public function testGetSourceNotFound(): void
    {
        $response = $this->applicationClient->makeGetSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            (new EntityIdFactory())->create()
        );

        $this->responseAsserter->assertNotFoundResponse($response);
    }

    /**
     * @dataProvider getSourceSuccessDataProvider
     *
     * @param callable(AuthenticationConfiguration $authenticationConfiguration): SourceInterface $sourceCreator
     * @param callable(SourceInterface $source): array<mixed> $expectedResponseDataCreator
     */
    public function testGetSuccess(callable $sourceCreator, callable $expectedResponseDataCreator): void
    {
        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);

        $source = $sourceCreator(self::$authenticationConfiguration);
        $sourceRepository->save($source);

        $response = $this->applicationClient->makeGetSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $source->getId()
        );

        $expectedResponseData = $expectedResponseDataCreator($source);

        $this->responseAsserter->assertSuccessfulJsonResponse($response, $expectedResponseData);
    }

    /**
     * @return array<mixed>
     */
    public function getSourceSuccessDataProvider(): array
    {
        return [
            'git source with credentials' => [
                'sourceCreator' => function (AuthenticationConfiguration $authenticationConfiguration) {
                    return GitSourceFactory::create(
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
                    return GitSourceFactory::create(
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
                    return FileSourceFactory::create(
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
            'run' => [
                'sourceCreator' => function (AuthenticationConfiguration $authenticationConfiguration) {
                    $parent = FileSourceFactory::create(
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id
                    );

                    return new RunSource((new EntityIdFactory())->create(), $parent);
                },
                'expectedResponseDataCreator' => function (RunSource $source) {
                    return [
                        'id' => $source->getId(),
                        'user_id' => $source->getUserId(),
                        'type' => Type::RUN->value,
                        'parameters' => [],
                        'state' => State::REQUESTED->value,
                    ];
                },
            ],
            'run, preparation failed' => [
                'sourceCreator' => function (AuthenticationConfiguration $authenticationConfiguration) {
                    $parent = FileSourceFactory::create(
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
