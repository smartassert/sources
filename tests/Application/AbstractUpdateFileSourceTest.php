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
use App\Tests\DataProvider\CreateUpdateFileSourceDataProviderTrait;
use App\Tests\Services\EntityRemover;
use App\Tests\Services\SourceOriginFactory;
use App\Tests\Services\SourceRequestTypeMatcher;
use SmartAssert\TestAuthenticationProviderBundle\UserProvider;

abstract class AbstractUpdateFileSourceTest extends AbstractApplicationTest
{
    use CreateUpdateFileSourceDataProviderTrait;

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
     *
     * @param array<string, string> $payload
     * @param array<mixed>          $expectedResponseData
     */
    public function testUpdateInvalidRequest(
        array $payload,
        array $expectedResponseData
    ): void {
        $source = SourceOriginFactory::create(
            type: Type::FILE->value,
            userId: self::$users->get(self::USER_1_EMAIL)->id
        );

        $this->sourceRepository->save($source);

        $response = $this->applicationClient->makeUpdateFileSourceRequest(
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
        $targetCreateResponse = $this->applicationClient->makeCreateFileSourceRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $targetCreateParameters
        );

        self::assertSame(200, $targetCreateResponse->getStatusCode());

        $targetCreateResponseData = json_decode($targetCreateResponse->getBody()->getContents(), true);
        \assert(is_array($targetCreateResponseData));
        $sourceId = $targetCreateResponseData['id'] ?? null;

        if (SourceRequestTypeMatcher::matchesGitSourceRequest($targetCreateParameters)) {
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

        $updateResponse = $this->applicationClient->makeUpdateFileSourceRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $sourceId,
            $updateParameters
        );

        $expectedResponseData = [
            'class' => 'duplicate',
            'field' => [
                'name' => 'label',
                'value' => $conflictCreateParameters['label'],
            ],
            'duplication_of' => 'entity label',
        ];

        self::assertJsonStringEqualsJsonString(
            (string) json_encode($expectedResponseData),
            $updateResponse->getBody()->getContents(),
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
            'file source with label of git source' => [
                'conflictSourceLabel' => $conflictSourceLabel,
                'targetCreateParameters' => [
                    GitSourceRequest::PARAMETER_LABEL => $targetSourceLabel,
                ],
                'conflictCreateParameters' => [
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
                    GitSourceRequest::PARAMETER_LABEL => $targetSourceLabel,
                ],
                'conflictCreateParameters' => [
                    GitSourceRequest::PARAMETER_LABEL => $conflictSourceLabel,
                ],
                'updateParameters' => [
                    GitSourceRequest::PARAMETER_LABEL => $conflictSourceLabel,
                ],
            ],
        ];
    }

    /**
     * @dataProvider updateSuccessDataProvider
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

        $response = $this->applicationClient->makeUpdateFileSourceRequest(
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
    public static function updateSuccessDataProvider(): array
    {
        return [
            'file source' => [
                'sourceCreator' => function (UserProvider $users) {
                    return SourceOriginFactory::create(
                        type: 'file',
                        userId: $users->get(self::USER_1_EMAIL)->id,
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

        $updateResponse = $this->applicationClient->makeUpdateFileSourceRequest(
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
            'file source using label of deleted file source' => [
                'targetSourceCreator' => function (UserProvider $users) {
                    return SourceOriginFactory::create(
                        type: 'file',
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
                'additionalUpdateParameters' => [],
            ],
            'file source using label of deleted git source' => [
                'targetSourceCreator' => function (UserProvider $users) {
                    return SourceOriginFactory::create(
                        type: 'file',
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
                'additionalUpdateParameters' => [],
            ],
        ];
    }

    public function testUpdateDeletedSource(): void
    {
        $source = SourceOriginFactory::create(
            type: 'file',
            userId: self::$users->get(self::USER_1_EMAIL)->id,
            label: 'original label',
        );
        $this->sourceRepository->save($source);
        $this->sourceRepository->delete($source);

        $response = $this->applicationClient->makeUpdateFileSourceRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $source->getId(),
            [
                FileSourceRequest::PARAMETER_LABEL => 'new label',
            ]
        );

        $expectedResponseData = [
            'class' => 'modify_read_only',
            'entity' => [
                'id' => $source->getId(),
                'type' => 'file-source',
            ],
        ];

        self::assertJsonStringEqualsJsonString(
            (string) json_encode($expectedResponseData),
            $response->getBody()->getContents(),
        );
    }
}
