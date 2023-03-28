<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\SourceInterface;
use App\Enum\Source\Type;
use App\Repository\SourceRepository;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use App\Request\OriginSourceRequest;
use App\Tests\DataProvider\CreateUpdateFileSourceDataProviderTrait;
use App\Tests\DataProvider\CreateUpdateGitSourceDataProviderTrait;
use App\Tests\Services\AuthenticationProvider\Provider;
use App\Tests\Services\EntityRemover;
use App\Tests\Services\SourceOriginFactory;

abstract class AbstractUpdateSourceTest extends AbstractApplicationTest
{
    use CreateUpdateFileSourceDataProviderTrait;
    use CreateUpdateGitSourceDataProviderTrait;

    private SourceRepository $sourceRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);
        $this->sourceRepository = $sourceRepository;

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        \assert($entityRemover instanceof EntityRemover);
        $entityRemover->removeAll();
    }

    /**
     * @dataProvider createUpdateFileSourceInvalidRequestDataProvider
     * @dataProvider createUpdateGitSourceInvalidRequestDataProvider
     *
     * @param array<string, string> $payload
     * @param array<mixed>          $expectedResponseData
     */
    public function testUpdateInvalidRequest(
        array $payload,
        array $expectedResponseData
    ): void {
        $source = SourceOriginFactory::create(
            type: $payload['type'] ?? '',
            userId: self::$authenticationConfiguration->getUser(self::USER_1_EMAIL)->id
        );

        $this->sourceRepository->save($source);

        $response = $this->applicationClient->makeUpdateSourceRequest(
            self::$authenticationConfiguration->getApiToken(self::USER_1_EMAIL),
            $source->getId(),
            $payload
        );

        $this->responseAsserter->assertInvalidRequestJsonResponse($response, $expectedResponseData);
    }

    /**
     * @dataProvider updateNewLabelNotUniqueDataProvider
     *
     * @param array<string, string> $targetCreateParameters
     * @param array<string, string> $conflictCreateParameters
     * @param array<string, string> $updateParameters
     */
    public function testUpdateNewLabelNotUnique(
        string $conflictSourceLabel,
        array $targetCreateParameters,
        array $conflictCreateParameters,
        array $updateParameters,
    ): void {
        $targetCreateResponse = $this->applicationClient->makeCreateSourceRequest(
            self::$authenticationConfiguration->getApiToken(self::USER_1_EMAIL),
            $targetCreateParameters
        );

        self::assertSame(200, $targetCreateResponse->getStatusCode());

        $targetCreateResponseData = json_decode($targetCreateResponse->getBody()->getContents(), true);
        \assert(is_array($targetCreateResponseData));
        $sourceId = $targetCreateResponseData['id'] ?? null;

        $conflictCreateResponse = $this->applicationClient->makeCreateSourceRequest(
            self::$authenticationConfiguration->getApiToken(self::USER_1_EMAIL),
            $conflictCreateParameters
        );

        self::assertSame(200, $conflictCreateResponse->getStatusCode());

        $updateResponse = $this->applicationClient->makeUpdateSourceRequest(
            self::$authenticationConfiguration->getApiToken(self::USER_1_EMAIL),
            $sourceId,
            $updateParameters
        );

        $this->responseAsserter->assertInvalidRequestJsonResponse(
            $updateResponse,
            [
                'error' => [
                    'type' => 'invalid_request',
                    'payload' => [
                        'name' => 'label',
                        'value' => $conflictSourceLabel,
                        'message' => 'This label is being used by another source belonging to this user',
                    ],
                ],
            ]
        );
    }

    /**
     * @return array<mixed>
     */
    public function updateNewLabelNotUniqueDataProvider(): array
    {
        $targetSourceLabel = md5((string) rand());
        $conflictSourceLabel = md5((string) rand());

        return [
            'file source with label of git source' => [
                'conflictSourceLabel' => $conflictSourceLabel,
                'targetCreateParameters' => [
                    OriginSourceRequest::PARAMETER_TYPE => Type::FILE->value,
                    GitSourceRequest::PARAMETER_LABEL => $targetSourceLabel,
                ],
                'conflictCreateParameters' => [
                    OriginSourceRequest::PARAMETER_TYPE => Type::GIT->value,
                    GitSourceRequest::PARAMETER_LABEL => $conflictSourceLabel,
                    GitSourceRequest::PARAMETER_HOST_URL => md5((string) rand()),
                    GitSourceRequest::PARAMETER_PATH => md5((string) rand()),
                ],
                'updateParameters' => [
                    GitSourceRequest::PARAMETER_LABEL => $conflictSourceLabel,
                ],
            ],
            'file source with label of file source' => [
                'conflictSourceLabel' => $conflictSourceLabel,
                'targetCreateParameters' => [
                    OriginSourceRequest::PARAMETER_TYPE => Type::FILE->value,
                    GitSourceRequest::PARAMETER_LABEL => $targetSourceLabel,
                ],
                'conflictCreateParameters' => [
                    OriginSourceRequest::PARAMETER_TYPE => Type::FILE->value,
                    GitSourceRequest::PARAMETER_LABEL => $conflictSourceLabel,
                ],
                'updateParameters' => [
                    GitSourceRequest::PARAMETER_LABEL => $conflictSourceLabel,
                ],
            ],
            'git source with label of file source' => [
                'conflictSourceLabel' => $conflictSourceLabel,
                'targetCreateParameters' => [
                    OriginSourceRequest::PARAMETER_TYPE => Type::GIT->value,
                    GitSourceRequest::PARAMETER_LABEL => $targetSourceLabel,
                    GitSourceRequest::PARAMETER_HOST_URL => md5((string) rand()),
                    GitSourceRequest::PARAMETER_PATH => md5((string) rand()),
                ],
                'conflictCreateParameters' => [
                    OriginSourceRequest::PARAMETER_TYPE => Type::FILE->value,
                    GitSourceRequest::PARAMETER_LABEL => $conflictSourceLabel,
                ],
                'updateParameters' => [
                    GitSourceRequest::PARAMETER_LABEL => $conflictSourceLabel,
                    GitSourceRequest::PARAMETER_HOST_URL => md5((string) rand()),
                    GitSourceRequest::PARAMETER_PATH => md5((string) rand()),
                ],
            ],
            'git source with label of git source' => [
                'conflictSourceLabel' => $conflictSourceLabel,
                'targetCreateParameters' => [
                    OriginSourceRequest::PARAMETER_TYPE => Type::GIT->value,
                    GitSourceRequest::PARAMETER_LABEL => $targetSourceLabel,
                    GitSourceRequest::PARAMETER_HOST_URL => md5((string) rand()),
                    GitSourceRequest::PARAMETER_PATH => md5((string) rand()),
                ],
                'conflictCreateParameters' => [
                    OriginSourceRequest::PARAMETER_TYPE => Type::GIT->value,
                    GitSourceRequest::PARAMETER_LABEL => $conflictSourceLabel,
                    GitSourceRequest::PARAMETER_HOST_URL => md5((string) rand()),
                    GitSourceRequest::PARAMETER_PATH => md5((string) rand()),
                ],
                'updateParameters' => [
                    GitSourceRequest::PARAMETER_LABEL => $conflictSourceLabel,
                    GitSourceRequest::PARAMETER_HOST_URL => md5((string) rand()),
                    GitSourceRequest::PARAMETER_PATH => md5((string) rand()),
                ],
            ],
        ];
    }

    /**
     * @dataProvider updateSourceSuccessDataProvider
     *
     * @param callable(Provider): SourceInterface     $sourceCreator
     * @param array<string, string>                   $payload
     * @param callable(SourceInterface): array<mixed> $expectedResponseDataCreator
     */
    public function testUpdateSuccess(
        callable $sourceCreator,
        array $payload,
        callable $expectedResponseDataCreator,
    ): void {
        $source = $sourceCreator(self::$authenticationConfiguration);
        $this->sourceRepository->save($source);

        $response = $this->applicationClient->makeUpdateSourceRequest(
            self::$authenticationConfiguration->getApiToken(self::USER_1_EMAIL),
            $source->getId(),
            $payload
        );

        $expectedResponseData = $expectedResponseDataCreator($source);

        $this->responseAsserter->assertSuccessfulJsonResponse($response, $expectedResponseData);
    }

    /**
     * @return array<mixed>
     */
    public function updateSourceSuccessDataProvider(): array
    {
        return [
            'git source, credentials present and empty' => [
                'sourceCreator' => function (Provider $authenticationConfiguration) {
                    return SourceOriginFactory::create(
                        type: 'git',
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                        label: 'original label',
                        hostUrl: 'https://example.com/original.git',
                        path: '/original',
                        credentials: 'credentials',
                    );
                },
                'payload' => [
                    GitSourceRequest::PARAMETER_LABEL => 'new label',
                    GitSourceRequest::PARAMETER_HOST_URL => 'https://example.com/new.git',
                    GitSourceRequest::PARAMETER_PATH => '/new',
                    GitSourceRequest::PARAMETER_CREDENTIALS => null,
                ],
                'expectedResponseDataCreator' => function (GitSource $source) {
                    return [
                        'id' => $source->getId(),
                        'user_id' => $source->getUserId(),
                        'type' => Type::GIT->value,
                        'label' => 'new label',
                        'host_url' => 'https://example.com/new.git',
                        'path' => '/new',
                        'has_credentials' => false,
                    ];
                },
            ],
            'git source, credentials not present' => [
                'sourceCreator' => function (Provider $authenticationConfiguration) {
                    return SourceOriginFactory::create(
                        type: 'git',
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                        label: 'original label',
                        hostUrl: 'https://example.com/original.git',
                        path: '/original',
                    );
                },
                'payload' => [
                    GitSourceRequest::PARAMETER_LABEL => 'new label',
                    GitSourceRequest::PARAMETER_HOST_URL => 'https://example.com/new.git',
                    GitSourceRequest::PARAMETER_PATH => '/new',
                ],
                'expectedResponseDataCreator' => function (GitSource $source) {
                    return [
                        'id' => $source->getId(),
                        'user_id' => $source->getUserId(),
                        'type' => Type::GIT->value,
                        'label' => 'new label',
                        'host_url' => 'https://example.com/new.git',
                        'path' => '/new',
                        'has_credentials' => false,
                    ];
                },
            ],
            'git source, update all but the label' => [
                'sourceCreator' => function (Provider $authenticationConfiguration) {
                    return SourceOriginFactory::create(
                        type: 'git',
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                        label: 'original label',
                        hostUrl: 'https://example.com/original.git',
                        path: '/original',
                    );
                },
                'payload' => [
                    GitSourceRequest::PARAMETER_LABEL => 'original label',
                    GitSourceRequest::PARAMETER_HOST_URL => 'https://example.com/new.git',
                    GitSourceRequest::PARAMETER_PATH => '/new',
                ],
                'expectedResponseDataCreator' => function (GitSource $source) {
                    return [
                        'id' => $source->getId(),
                        'user_id' => $source->getUserId(),
                        'type' => Type::GIT->value,
                        'label' => 'original label',
                        'host_url' => 'https://example.com/new.git',
                        'path' => '/new',
                        'has_credentials' => false,
                    ];
                },
            ],
            'file source' => [
                'sourceCreator' => function (Provider $authenticationConfiguration) {
                    return SourceOriginFactory::create(
                        type: 'file',
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                        label: 'original label',
                    );
                },
                'payload' => [
                    FileSourceRequest::PARAMETER_LABEL => 'new label',
                ],
                'expectedResponseDataCreator' => function (FileSource $source) {
                    return [
                        'id' => $source->getId(),
                        'user_id' => $source->getUserId(),
                        'type' => Type::FILE->value,
                        'label' => 'new label',
                    ];
                },
            ],
        ];
    }

    /**
     * @dataProvider updateNewLabelUsedByDeletedSourceDataProvider
     *
     * @param callable(Provider): SourceInterface $targetSourceCreator
     * @param callable(Provider): SourceInterface $deletedSourceCreator
     * @param array<string, string>               $additionalUpdateParameters
     */
    public function testUpdateNewLabelUsedByDeletedSource(
        callable $targetSourceCreator,
        callable $deletedSourceCreator,
        array $additionalUpdateParameters,
    ): void {
        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);

        $source = $targetSourceCreator(self::$authenticationConfiguration);
        $this->sourceRepository->save($source);

        $deletedSource = $deletedSourceCreator(self::$authenticationConfiguration);
        \assert($deletedSource instanceof FileSource || $deletedSource instanceof GitSource);
        $this->sourceRepository->save($deletedSource);

        $this->applicationClient->makeDeleteSourceRequest(
            self::$authenticationConfiguration->getApiToken(self::USER_1_EMAIL),
            $deletedSource->getId(),
        );

        $updateResponse = $this->applicationClient->makeUpdateSourceRequest(
            self::$authenticationConfiguration->getApiToken(self::USER_1_EMAIL),
            $source->getId(),
            array_merge(
                [
                    'label' => $deletedSource->getLabel(),
                ],
                $additionalUpdateParameters
            )
        );

        self::assertSame(200, $updateResponse->getStatusCode());
    }

    /**
     * @return array<mixed>
     */
    public function updateNewLabelUsedByDeletedSourceDataProvider(): array
    {
        return [
            'file source using label of deleted file source' => [
                'targetSourceCreator' => function (Provider $authenticationConfiguration) {
                    return SourceOriginFactory::create(
                        type: 'file',
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                        label: 'label1',
                    );
                },
                'deletedSourceCreator' => function (Provider $authenticationConfiguration) {
                    return SourceOriginFactory::create(
                        type: 'file',
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                        label: 'label2',
                    );
                },
                'additionalUpdateParameters' => [],
            ],
            'file source using label of deleted git source' => [
                'targetSourceCreator' => function (Provider $authenticationConfiguration) {
                    return SourceOriginFactory::create(
                        type: 'file',
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                        label: 'label1',
                    );
                },
                'deletedSourceCreator' => function (Provider $authenticationConfiguration) {
                    return SourceOriginFactory::create(
                        type: 'git',
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                        label: 'label2',
                    );
                },
                'additionalUpdateParameters' => [],
            ],
            'git source using label of deleted file source' => [
                'targetSourceCreator' => function (Provider $authenticationConfiguration) {
                    return SourceOriginFactory::create(
                        type: 'git',
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                        label: 'label1',
                    );
                },
                'deletedSourceCreator' => function (Provider $authenticationConfiguration) {
                    return SourceOriginFactory::create(
                        type: 'file',
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                        label: 'label2',
                    );
                },
                'additionalUpdateParameters' => [
                    GitSourceRequest::PARAMETER_HOST_URL => md5((string) rand()),
                    GitSourceRequest::PARAMETER_PATH => md5((string) rand()),
                ],
            ],
            'git source using label of deleted git source' => [
                'targetSourceCreator' => function (Provider $authenticationConfiguration) {
                    return SourceOriginFactory::create(
                        type: 'git',
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                        label: 'label1',
                    );
                },
                'deletedSourceCreator' => function (Provider $authenticationConfiguration) {
                    return SourceOriginFactory::create(
                        type: 'git',
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                        label: 'label2',
                    );
                },
                'additionalUpdateParameters' => [
                    GitSourceRequest::PARAMETER_HOST_URL => md5((string) rand()),
                    GitSourceRequest::PARAMETER_PATH => md5((string) rand()),
                ],
            ],
        ];
    }

    public function testUpdateDeletedSource(): void
    {
        $source = SourceOriginFactory::create(
            type: 'file',
            userId: self::$authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
            label: 'original label',
        );
        $this->sourceRepository->save($source);
        $this->sourceRepository->delete($source);

        $response = $this->applicationClient->makeUpdateSourceRequest(
            self::$authenticationConfiguration->getApiToken(self::USER_1_EMAIL),
            $source->getId(),
            [
                FileSourceRequest::PARAMETER_LABEL => 'new label',
            ]
        );

        $this->responseAsserter->assertMethodNotAllowedResponse(
            $response,
            [
                'error' => [
                    'type' => 'modify-read-only-entity',
                    'payload' => [
                        'type' => 'source',
                        'id' => $source->getId(),
                    ],
                ],
            ]
        );
    }
}
