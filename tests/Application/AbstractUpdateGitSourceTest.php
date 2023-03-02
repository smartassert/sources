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
use App\Tests\Services\FileSourceFactory;
use App\Tests\Services\GitSourceFactory;

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
        $source = FileSourceFactory::create(
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
        $source = GitSourceFactory::create(
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

    public function testUpdateNewLabelNotUnique(): void
    {
        $gitSourceRepository = self::getContainer()->get(GitSourceRepository::class);
        \assert($gitSourceRepository instanceof GitSourceRepository);

        $source = GitSourceFactory::create(
            userId: self::$authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
            label: 'label1'
        );
        $this->sourceRepository->save($source);

        $this->sourceRepository->save(
            GitSourceFactory::create(
                userId: self::$authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                label: 'label2'
            )
        );

        self::assertSame(1, $gitSourceRepository->count(['label' => 'label1']));

        $updateResponse = $this->applicationClient->makeUpdateGitSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $source->getId(),
            [
                GitSourceRequest::PARAMETER_LABEL => 'label2',
                GitSourceRequest::PARAMETER_HOST_URL => 'https://example.com/' . md5((string) rand()) . '.git',
                GitSourceRequest::PARAMETER_PATH => md5((string) rand()),
            ]
        );

        $this->responseAsserter->assertInvalidRequestJsonResponse(
            $updateResponse,
            [
                'error' => [
                    'type' => 'invalid_request',
                    'payload' => [
                        'name' => 'label',
                        'value' => 'label2',
                        'message' => 'This label is being used by another git source belonging to this user',
                    ],
                ],
            ]
        );
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
                    return GitSourceFactory::create(
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
            Type::GIT->value . ' credentials not present' => [
                'sourceCreator' => function (AuthenticationConfiguration $authenticationConfiguration) {
                    return GitSourceFactory::create(
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
            Type::GIT->value . ' update all but the label' => [
                'sourceCreator' => function (AuthenticationConfiguration $authenticationConfiguration) {
                    return GitSourceFactory::create(
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
        ];
    }

    public function testCreateWithLabelOfDeletedSourceIsSuccessful(): void
    {
        $label = 'git source label';
        $requestParameters = [
            OriginSourceRequest::PARAMETER_TYPE => Type::GIT->value,
            GitSourceRequest::PARAMETER_LABEL => $label,
            GitSourceRequest::PARAMETER_HOST_URL => md5((string) rand()),
            GitSourceRequest::PARAMETER_PATH => md5((string) rand()),
        ];

        $firstCreateResponse = $this->applicationClient->makeCreateSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $requestParameters
        );

        self::assertSame(200, $firstCreateResponse->getStatusCode());

        $firstCreateResponseData = json_decode($firstCreateResponse->getBody()->getContents(), true);
        \assert(is_array($firstCreateResponseData));
        $sourceId = $firstCreateResponseData['id'] ?? null;
        \assert(is_string($sourceId));

        $deleteResponse = $this->applicationClient->makeDeleteSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $sourceId
        );

        self::assertSame(200, $deleteResponse->getStatusCode());

        $secondCreateResponse = $this->applicationClient->makeCreateSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $requestParameters
        );

        self::assertSame(200, $secondCreateResponse->getStatusCode());

        $secondCreateResponseData = json_decode($secondCreateResponse->getBody()->getContents(), true);
        \assert(is_array($secondCreateResponseData));
        self::assertNotSame($sourceId, $secondCreateResponseData['id']);
    }
}
