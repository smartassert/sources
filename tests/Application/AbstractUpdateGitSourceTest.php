<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\GitSource;
use App\Enum\Source\Type;
use App\Repository\GitSourceRepository;
use App\Repository\SourceRepository;
use App\Request\GitSourceRequest;
use App\Request\OriginSourceRequest;
use App\Tests\DataProvider\CreateUpdateGitSourceDataProviderTrait;
use App\Tests\Services\AuthenticationConfiguration;
use App\Tests\Services\SourceOriginFactory;

abstract class AbstractUpdateGitSourceTest extends AbstractApplicationTest
{
    use CreateUpdateGitSourceDataProviderTrait;

    private SourceRepository $sourceRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);
        $this->sourceRepository = $sourceRepository;
    }

    public function testUpdateInvalidSourceType(): void
    {
        $source = SourceOriginFactory::create(
            type: 'file',
            userId: self::$authenticationConfiguration->getUser(self::USER_1_EMAIL)->id
        );
        $this->sourceRepository->save($source);

        $response = $this->applicationClient->makeUpdateGitSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $source->getId(),
            []
        );

        $this->responseAsserter->assertNotFoundResponse($response);
    }

    /**
     * @dataProvider createUpdateGitSourceInvalidRequestDataProvider
     *
     * @param array<string, string> $payload
     * @param array<mixed>          $expectedResponseData
     */
    public function testUpdateInvalidRequest(array $payload, array $expectedResponseData): void
    {
        $source = SourceOriginFactory::create(
            type: 'git',
            userId: self::$authenticationConfiguration->getUser(self::USER_1_EMAIL)->id
        );
        $this->sourceRepository->save($source);

        $response = $this->applicationClient->makeUpdateGitSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
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
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $targetCreateParameters
        );

        self::assertSame(200, $targetCreateResponse->getStatusCode());

        $targetCreateResponseData = json_decode($targetCreateResponse->getBody()->getContents(), true);
        \assert(is_array($targetCreateResponseData));
        $sourceId = $targetCreateResponseData['id'] ?? null;

        $conflictCreateResponse = $this->applicationClient->makeCreateSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $conflictCreateParameters
        );

        self::assertSame(200, $conflictCreateResponse->getStatusCode());

        $updateResponse = $this->applicationClient->makeUpdateGitSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
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
                    OriginSourceRequest::PARAMETER_TYPE => Type::GIT->value,
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
                    OriginSourceRequest::PARAMETER_TYPE => Type::GIT->value,
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
     * @param callable(AuthenticationConfiguration $authenticationConfiguration): GitSource $sourceCreator
     * @param array<string, string> $payload
     * @param callable(GitSource $source): array<mixed> $expectedResponseDataCreator
     */
    public function testUpdateSuccess(
        callable $sourceCreator,
        array $payload,
        callable $expectedResponseDataCreator,
    ): void {
        $source = $sourceCreator(self::$authenticationConfiguration);
        $this->sourceRepository->save($source);

        $response = $this->applicationClient->makeUpdateGitSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
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
            Type::GIT->value . ' credentials present and empty' => [
                'sourceCreator' => function (AuthenticationConfiguration $authenticationConfiguration) {
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
                    OriginSourceRequest::PARAMETER_TYPE => Type::GIT->value,
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
            Type::GIT->value . ' credentials not present' => [
                'sourceCreator' => function (AuthenticationConfiguration $authenticationConfiguration) {
                    return SourceOriginFactory::create(
                        type: 'git',
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                        label: 'original label',
                        hostUrl: 'https://example.com/original.git',
                        path: '/original',
                    );
                },
                'payload' => [
                    OriginSourceRequest::PARAMETER_TYPE => Type::GIT->value,
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
            Type::GIT->value . ' update all but the label' => [
                'sourceCreator' => function (AuthenticationConfiguration $authenticationConfiguration) {
                    return SourceOriginFactory::create(
                        type: 'git',
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                        label: 'original label',
                        hostUrl: 'https://example.com/original.git',
                        path: '/original',
                    );
                },
                'payload' => [
                    OriginSourceRequest::PARAMETER_TYPE => Type::GIT->value,
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
        ];
    }

    public function testUpdateNewLabelUsedByDeletedSource(): void
    {
        $gitSourceRepository = self::getContainer()->get(GitSourceRepository::class);
        \assert($gitSourceRepository instanceof GitSourceRepository);

        $source = SourceOriginFactory::create(
            type: 'git',
            userId: self::$authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
            label: 'label1',
        );
        $this->sourceRepository->save($source);
        \assert($source instanceof GitSource);

        $sourceToBeDeleted = SourceOriginFactory::create(
            type: 'git',
            userId: self::$authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
            label: 'label2',
        );
        $this->sourceRepository->save($sourceToBeDeleted);

        self::assertSame(1, $gitSourceRepository->count(['label' => 'label1', 'deletedAt' => null]));
        self::assertSame(1, $gitSourceRepository->count(['label' => 'label2', 'deletedAt' => null]));

        $this->applicationClient->makeDeleteSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $sourceToBeDeleted->getId(),
        );

        self::assertSame(1, $gitSourceRepository->count(['label' => 'label1', 'deletedAt' => null]));
        self::assertSame(0, $gitSourceRepository->count(['label' => 'label2', 'deletedAt' => null]));

        $updateResponse = $this->applicationClient->makeUpdateGitSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $source->getId(),
            [
                OriginSourceRequest::PARAMETER_TYPE => Type::GIT->value,
                GitSourceRequest::PARAMETER_LABEL => 'label2',
                GitSourceRequest::PARAMETER_HOST_URL => $source->getHostUrl(),
                GitSourceRequest::PARAMETER_PATH => $source->getPath(),
            ]
        );

        self::assertSame(200, $updateResponse->getStatusCode());
    }
}
