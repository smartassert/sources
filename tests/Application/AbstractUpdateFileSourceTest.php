<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Enum\Source\Type;
use App\Repository\FileSourceRepository;
use App\Repository\SourceRepository;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use App\Request\OriginSourceRequest;
use App\Tests\DataProvider\CreateUpdateFileSourceDataProviderTrait;
use App\Tests\Services\SourceOriginFactory;

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
    }

    public function testUpdateInvalidSourceType(): void
    {
        $source = SourceOriginFactory::create(
            type: 'git',
            userId: self::$authenticationConfiguration->getUser(self::USER_1_EMAIL)->id
        );

        $this->sourceRepository->save($source);

        $response = $this->applicationClient->makeUpdateFileSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $source->getId(),
            [
                OriginSourceRequest::PARAMETER_TYPE => 'invalid source type',
            ]
        );

        $this->responseAsserter->assertInvalidRequestJsonResponse(
            $response,
            [
                'error' => [
                    'type' => 'invalid_request',
                    'payload' => [
                        'name' => 'type',
                        'value' => 'invalid source type',
                        'message' => 'Source type must be one of: file, git.',
                    ],
                ],
            ]
        );
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
            type: 'file',
            userId: self::$authenticationConfiguration->getUser(self::USER_1_EMAIL)->id
        );

        $this->sourceRepository->save($source);

        $response = $this->applicationClient->makeUpdateFileSourceRequest(
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

        $updateResponse = $this->applicationClient->makeUpdateFileSourceRequest(
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
                    OriginSourceRequest::PARAMETER_TYPE => Type::FILE->value,
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
                    OriginSourceRequest::PARAMETER_TYPE => Type::FILE->value,
                    GitSourceRequest::PARAMETER_LABEL => $conflictSourceLabel,
                ],
            ],
        ];
    }

    public function testUpdateSuccess(): void
    {
        $source = SourceOriginFactory::create(
            type: 'file',
            userId: self::$authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
            label: 'original label'
        );
        $this->sourceRepository->save($source);

        $response = $this->applicationClient->makeUpdateFileSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $source->getId(),
            [
                OriginSourceRequest::PARAMETER_TYPE => Type::FILE->value,
                FileSourceRequest::PARAMETER_LABEL => 'new label',
            ]
        );

        $this->responseAsserter->assertSuccessfulJsonResponse(
            $response,
            [
                'id' => $source->getId(),
                'user_id' => $source->getUserId(),
                'type' => Type::FILE->value,
                'label' => 'new label',
            ]
        );
    }

    public function testUpdateNewLabelUsedByDeletedSource(): void
    {
        $fileSourceRepository = self::getContainer()->get(FileSourceRepository::class);
        \assert($fileSourceRepository instanceof FileSourceRepository);

        $source = SourceOriginFactory::create(
            type: 'file',
            userId: self::$authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
            label: 'label1',
        );
        $this->sourceRepository->save($source);

        $sourceToBeDeleted = SourceOriginFactory::create(
            type: 'file',
            userId: self::$authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
            label: 'label2',
        );
        $this->sourceRepository->save($sourceToBeDeleted);

        self::assertSame(1, $fileSourceRepository->count(['label' => 'label1', 'deletedAt' => null]));
        self::assertSame(1, $fileSourceRepository->count(['label' => 'label2', 'deletedAt' => null]));

        $this->applicationClient->makeDeleteSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $sourceToBeDeleted->getId(),
        );

        self::assertSame(1, $fileSourceRepository->count(['label' => 'label1', 'deletedAt' => null]));
        self::assertSame(0, $fileSourceRepository->count(['label' => 'label2', 'deletedAt' => null]));

        $updateResponse = $this->applicationClient->makeUpdateFileSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $source->getId(),
            [
                OriginSourceRequest::PARAMETER_TYPE => Type::FILE->value,
                FileSourceRequest::PARAMETER_LABEL => 'label2',
            ]
        );

        self::assertSame(200, $updateResponse->getStatusCode());
    }
}
