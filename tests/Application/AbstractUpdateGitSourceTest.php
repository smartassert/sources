<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\SourceInterface;
use App\Enum\Source\Type;
use App\Repository\SourceRepository;
use App\Request\GitSourceRequest;
use App\Tests\DataProvider\CreateUpdateGitSourceDataProviderTrait;
use App\Tests\Services\EntityRemover;
use App\Tests\Services\SourceOriginFactory;
use App\Tests\Services\SourceRequestTypeMatcher;
use SmartAssert\TestAuthenticationProviderBundle\UserProvider;

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

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        \assert($entityRemover instanceof EntityRemover);
        $entityRemover->removeAll();
    }

    /**
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
            type: Type::GIT->value,
            userId: self::$users->get(self::USER_1_EMAIL)->id
        );

        $this->sourceRepository->save($source);

        $response = $this->applicationClient->makeUpdateGitSourceRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
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
        $targetCreateResponse = $this->applicationClient->makeCreateGitSourceRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $targetCreateParameters
        );

        self::assertSame(200, $targetCreateResponse->getStatusCode());

        $targetCreateResponseData = json_decode($targetCreateResponse->getBody()->getContents(), true);
        \assert(is_array($targetCreateResponseData));
        $sourceId = $targetCreateResponseData['id'] ?? null;

        if (SourceRequestTypeMatcher::matchesGitSourceRequest($conflictCreateParameters)) {
            $conflictCreateResponse = $this->applicationClient->makeCreateGitSourceRequest(
                self::$apiTokens->get(self::USER_1_EMAIL),
                $conflictCreateParameters
            );
        } else {
            $conflictCreateResponse = $this->applicationClient->makeCreateFileSourceRequest(
                self::$apiTokens->get(self::USER_1_EMAIL),
                $conflictCreateParameters
            );
        }

        self::assertSame(200, $conflictCreateResponse->getStatusCode());

        $updateResponse = $this->applicationClient->makeUpdateGitSourceRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
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
    public static function updateNewLabelNotUniqueDataProvider(): array
    {
        $targetSourceLabel = md5((string) rand());
        $conflictSourceLabel = md5((string) rand());

        return [
            'git source with label of file source' => [
                'conflictSourceLabel' => $conflictSourceLabel,
                'targetCreateParameters' => [
                    GitSourceRequest::PARAMETER_LABEL => $targetSourceLabel,
                    GitSourceRequest::PARAMETER_HOST_URL => md5((string) rand()),
                    GitSourceRequest::PARAMETER_PATH => md5((string) rand()),
                ],
                'conflictCreateParameters' => [
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
                    GitSourceRequest::PARAMETER_LABEL => $targetSourceLabel,
                    GitSourceRequest::PARAMETER_HOST_URL => md5((string) rand()),
                    GitSourceRequest::PARAMETER_PATH => md5((string) rand()),
                ],
                'conflictCreateParameters' => [
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
     * @param callable(UserProvider): SourceInterface $sourceCreator
     * @param array<string, string>                   $payload
     * @param callable(SourceInterface): array<mixed> $expectedResponseDataCreator
     */
    public function testUpdateSuccess(
        callable $sourceCreator,
        array $payload,
        callable $expectedResponseDataCreator,
    ): void {
        $source = $sourceCreator(self::$users);
        $this->sourceRepository->save($source);

        $response = $this->applicationClient->makeUpdateGitSourceRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $source->getId(),
            $payload
        );

        $expectedResponseData = $expectedResponseDataCreator($source);

        $this->responseAsserter->assertSuccessfulJsonResponse($response, $expectedResponseData);
    }

    /**
     * @return array<mixed>
     */
    public static function updateSourceSuccessDataProvider(): array
    {
        return [
            'git source, credentials present and empty' => [
                'sourceCreator' => function (UserProvider $users) {
                    return SourceOriginFactory::create(
                        type: 'git',
                        userId: $users->get(self::USER_1_EMAIL)->id,
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
                'sourceCreator' => function (UserProvider $users) {
                    return SourceOriginFactory::create(
                        type: 'git',
                        userId: $users->get(self::USER_1_EMAIL)->id,
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
                'sourceCreator' => function (UserProvider $users) {
                    return SourceOriginFactory::create(
                        type: 'git',
                        userId: $users->get(self::USER_1_EMAIL)->id,
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

    /**
     * @dataProvider updateNewLabelUsedByDeletedSourceDataProvider
     *
     * @param callable(UserProvider): SourceInterface $targetSourceCreator
     * @param callable(UserProvider): SourceInterface $deletedSourceCreator
     * @param array<string, string>                   $additionalUpdateParameters
     */
    public function testUpdateNewLabelUsedByDeletedSource(
        callable $targetSourceCreator,
        callable $deletedSourceCreator,
        array $additionalUpdateParameters,
    ): void {
        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);

        $source = $targetSourceCreator(self::$users);
        $this->sourceRepository->save($source);

        $deletedSource = $deletedSourceCreator(self::$users);
        \assert($deletedSource instanceof FileSource || $deletedSource instanceof GitSource);
        $this->sourceRepository->save($deletedSource);

        $this->applicationClient->makeDeleteSourceRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $deletedSource->getId(),
        );

        $updateResponse = $this->applicationClient->makeUpdateGitSourceRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
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
    public static function updateNewLabelUsedByDeletedSourceDataProvider(): array
    {
        return [
            'git source using label of deleted file source' => [
                'targetSourceCreator' => function (UserProvider $users) {
                    return SourceOriginFactory::create(
                        type: 'git',
                        userId: $users->get(self::USER_1_EMAIL)->id,
                        label: 'label1',
                    );
                },
                'deletedSourceCreator' => function (UserProvider $users) {
                    return SourceOriginFactory::create(
                        type: 'file',
                        userId: $users->get(self::USER_1_EMAIL)->id,
                        label: 'label2',
                    );
                },
                'additionalUpdateParameters' => [
                    GitSourceRequest::PARAMETER_HOST_URL => md5((string) rand()),
                    GitSourceRequest::PARAMETER_PATH => md5((string) rand()),
                ],
            ],
            'git source using label of deleted git source' => [
                'targetSourceCreator' => function (UserProvider $users) {
                    return SourceOriginFactory::create(
                        type: 'git',
                        userId: $users->get(self::USER_1_EMAIL)->id,
                        label: 'label1',
                    );
                },
                'deletedSourceCreator' => function (UserProvider $users) {
                    return SourceOriginFactory::create(
                        type: 'git',
                        userId: $users->get(self::USER_1_EMAIL)->id,
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
            type: Type::GIT->value,
            userId: self::$users->get(self::USER_1_EMAIL)->id,
        );
        $this->sourceRepository->save($source);
        $this->sourceRepository->delete($source);

        $response = $this->applicationClient->makeUpdateGitSourceRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $source->getId(),
            [
                GitSourceRequest::PARAMETER_LABEL => 'new label',
                GitSourceRequest::PARAMETER_HOST_URL => md5((string) rand()),
                GitSourceRequest::PARAMETER_PATH => md5((string) rand()),
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
