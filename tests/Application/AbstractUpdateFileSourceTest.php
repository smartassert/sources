<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Enum\Source\Type;
use App\Repository\FileSourceRepository;
use App\Request\FileSourceRequest;
use App\Tests\DataProvider\CreateUpdateFileSourceDataProviderTrait;
use App\Tests\Services\SourceProvider;

abstract class AbstractUpdateFileSourceTest extends AbstractApplicationTest
{
    use CreateUpdateFileSourceDataProviderTrait;

    private SourceProvider $sourceProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $sourceProvider = self::getContainer()->get(SourceProvider::class);
        \assert($sourceProvider instanceof SourceProvider);
        $sourceProvider->setUserId(self::$authenticationConfiguration->getUser(self::USER_1_EMAIL)->id);
        $this->sourceProvider = $sourceProvider;
    }

    public function testUpdateInvalidSourceType(): void
    {
        $sourceIdentifier = SourceProvider::GIT_WITHOUT_CREDENTIALS_WITHOUT_RUN_SOURCE;

        $this->sourceProvider->initialize([$sourceIdentifier]);
        $source = $this->sourceProvider->get($sourceIdentifier);

        $response = $this->applicationClient->makeUpdateFileSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $source->getId(),
            []
        );

        $this->responseAsserter->assertNotFoundResponse($response);
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
        $sourceIdentifier = SourceProvider::FILE_WITHOUT_RUN_SOURCE;

        $this->sourceProvider->initialize([$sourceIdentifier]);
        $source = $this->sourceProvider->get($sourceIdentifier);

        $response = $this->applicationClient->makeUpdateFileSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $source->getId(),
            $payload
        );

        $this->responseAsserter->assertInvalidRequestJsonResponse($response, $expectedResponseData);
    }

    public function testUpdateNewLabelNotUnique(): void
    {
        $fileSourceRepository = self::getContainer()->get(FileSourceRepository::class);
        \assert($fileSourceRepository instanceof FileSourceRepository);

        $sourceId = $this->createFileSource(self::USER_1_EMAIL, 'label1');
        $this->createFileSource(self::USER_1_EMAIL, 'label2');

        self::assertSame(1, $fileSourceRepository->count(['label' => 'label1']));

        $updateResponse = $this->applicationClient->makeUpdateFileSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $sourceId,
            [
                FileSourceRequest::PARAMETER_LABEL => 'label2',
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
                        'message' => 'This label is being used by another file source belonging to this user',
                    ],
                ],
            ]
        );
    }

    /**
     * @dataProvider updateSourceSuccessDataProvider
     *
     * @param array<string, string> $payload
     * @param array<mixed>          $expectedResponseData
     */
    public function testUpdateSuccess(
        string $sourceIdentifier,
        array $payload,
        array $expectedResponseData
    ): void {
        $this->sourceProvider->initialize([$sourceIdentifier]);
        $source = $this->sourceProvider->get($sourceIdentifier);

        $response = $this->applicationClient->makeUpdateFileSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $source->getId(),
            $payload
        );

        $expectedResponseData['id'] = $source->getId();
        $expectedResponseData['user_id'] = $source->getUserId();

        $this->responseAsserter->assertSuccessfulJsonResponse($response, $expectedResponseData);
    }

    /**
     * @return array<mixed>
     */
    public function updateSourceSuccessDataProvider(): array
    {
        $newLabel = 'new file source label';

        return [
            Type::FILE->value => [
                'sourceIdentifier' => SourceProvider::FILE_WITHOUT_RUN_SOURCE,
                'payload' => [
                    FileSourceRequest::PARAMETER_LABEL => $newLabel,
                ],
                'expectedResponseData' => [
                    'type' => Type::FILE->value,
                    'label' => $newLabel,
                ],
            ],
        ];
    }

    public function testUpdateNewLabelUsedByDeletedSource(): void
    {
        $fileSourceRepository = self::getContainer()->get(FileSourceRepository::class);
        \assert($fileSourceRepository instanceof FileSourceRepository);

        $sourceId = $this->createFileSource(self::USER_1_EMAIL, 'label1');
        $sourceToBeDeletedId = $this->createFileSource(self::USER_1_EMAIL, 'label2');

        self::assertSame(1, $fileSourceRepository->count(['label' => 'label1', 'deletedAt' => null]));
        self::assertSame(1, $fileSourceRepository->count(['label' => 'label2', 'deletedAt' => null]));

        $this->applicationClient->makeDeleteSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $sourceToBeDeletedId,
        );

        self::assertSame(1, $fileSourceRepository->count(['label' => 'label1', 'deletedAt' => null]));
        self::assertSame(0, $fileSourceRepository->count(['label' => 'label2', 'deletedAt' => null]));

        $updateResponse = $this->applicationClient->makeUpdateFileSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $sourceId,
            [
                FileSourceRequest::PARAMETER_LABEL => 'label2',
            ]
        );

        self::assertSame(200, $updateResponse->getStatusCode());
    }

    private function createFileSource(string $userEmail, string $label): string
    {
        $response = $this->applicationClient->makeCreateFileSourceRequest(
            self::$authenticationConfiguration->getValidApiToken($userEmail),
            [
                FileSourceRequest::PARAMETER_LABEL => $label,
            ]
        );

        $responseData = json_decode($response->getBody()->getContents(), true);
        \assert(is_array($responseData));
        $sourceId = $responseData['id'] ?? null;
        \assert(is_string($sourceId));

        return $sourceId;
    }
}
