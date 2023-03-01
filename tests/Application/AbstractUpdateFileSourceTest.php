<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Enum\Source\Type;
use App\Repository\FileSourceRepository;
use App\Repository\SourceRepository;
use App\Request\FileSourceRequest;
use App\Tests\DataProvider\CreateUpdateFileSourceDataProviderTrait;
use App\Tests\Services\FileSourceFactory;
use App\Tests\Services\GitSourceFactory;

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
        $source = GitSourceFactory::create(
            userId: self::$authenticationConfiguration->getUser(self::USER_1_EMAIL)->id
        );

        $this->sourceRepository->save($source);

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
        $source = FileSourceFactory::create(
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

    public function testUpdateNewLabelNotUnique(): void
    {
        $source = FileSourceFactory::create(
            userId: self::$authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
            label: 'label1'
        );
        $this->sourceRepository->save($source);

        $this->sourceRepository->save(
            FileSourceFactory::create(
                userId: self::$authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                label: 'label2'
            )
        );

        $updateResponse = $this->applicationClient->makeUpdateFileSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $source->getId(),
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

    public function testUpdateSuccess(): void
    {
        $source = FileSourceFactory::create(
            userId: self::$authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
            label: 'original label'
        );
        $this->sourceRepository->save($source);

        $response = $this->applicationClient->makeUpdateFileSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $source->getId(),
            [
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

        $source = FileSourceFactory::create(
            userId: self::$authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
            label: 'label1',
        );
        $this->sourceRepository->save($source);

        $sourceToBeDeleted = FileSourceFactory::create(
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
                FileSourceRequest::PARAMETER_LABEL => 'label2',
            ]
        );

        self::assertSame(200, $updateResponse->getStatusCode());
    }
}
