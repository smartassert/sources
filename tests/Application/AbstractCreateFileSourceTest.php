<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\SourceInterface;
use App\Enum\Source\Type;
use App\Repository\SourceRepository;
use App\Request\FileSourceRequest;
use App\Tests\DataProvider\CreateUpdateFileSourceDataProviderTrait;

abstract class AbstractCreateFileSourceTest extends AbstractApplicationTest
{
    use CreateUpdateFileSourceDataProviderTrait;

    /**
     * @dataProvider createUpdateFileSourceInvalidRequestDataProvider
     *
     * @param array<string, string> $requestParameters
     * @param array<string, string> $expectedResponseData
     */
    public function testCreateInvalidSourceRequest(array $requestParameters, array $expectedResponseData): void
    {
        $response = $this->applicationClient->makeCreateFileSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_EMAIL),
            $requestParameters
        );

        $this->responseAsserter->assertInvalidRequestJsonResponse($response, $expectedResponseData);
    }

    /**
     * @dataProvider createSourceSuccessDataProvider
     *
     * @param array<string, string> $requestParameters
     * @param array<mixed>          $expected
     */
    public function testCreateSuccess(array $requestParameters, array $expected): void
    {
        $response = $this->applicationClient->makeCreateFileSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_EMAIL),
            $requestParameters
        );

        $sources = [];
        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        if ($sourceRepository instanceof SourceRepository) {
            $sources = $sourceRepository->findAll();
        }

        self::assertIsArray($sources);
        self::assertCount(1, $sources);

        $source = $sources[0];
        self::assertInstanceOf(SourceInterface::class, $source);

        $expected['id'] = $source->getId();
        $expected['user_id'] = self::$authenticationConfiguration->getUser(self::USER_EMAIL)->id;

        $this->responseAsserter->assertSuccessfulJsonResponse($response, $expected);
    }

    /**
     * @return array<mixed>
     */
    public function createSourceSuccessDataProvider(): array
    {
        $label = 'file source label';

        return [
            'file source' => [
                'requestParameters' => [
                    FileSourceRequest::PARAMETER_LABEL => $label
                ],
                'expected' => [
                    'type' => Type::FILE->value,
                    'label' => $label,
                ],
            ],
        ];
    }

    public function testCreateIsIdempotent(): void
    {
        $label = 'file source label';
        $requestParameters = [
            FileSourceRequest::PARAMETER_LABEL => $label,
        ];

        $firstResponse = $this->applicationClient->makeCreateFileSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_EMAIL),
            $requestParameters
        );

        self::assertSame(200, $firstResponse->getStatusCode());

        $secondResponse = $this->applicationClient->makeCreateFileSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_EMAIL),
            $requestParameters
        );

        self::assertSame(200, $secondResponse->getStatusCode());
        self::assertSame($firstResponse->getBody()->getContents(), $secondResponse->getBody()->getContents());
    }

    public function testCreateWithLabelOfDeletedSourceIsSuccessful(): void
    {
        $label = 'file source label';
        $requestParameters = [
            FileSourceRequest::PARAMETER_LABEL => $label,
        ];

        $firstCreateResponse = $this->applicationClient->makeCreateFileSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_EMAIL),
            $requestParameters
        );

        self::assertSame(200, $firstCreateResponse->getStatusCode());

        $firstCreateResponseData = json_decode($firstCreateResponse->getBody()->getContents(), true);
        \assert(is_array($firstCreateResponseData));
        $sourceId = $firstCreateResponseData['id'] ?? null;
        \assert(is_string($sourceId));

        $deleteResponse = $this->applicationClient->makeDeleteSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_EMAIL),
            $sourceId
        );

        self::assertSame(200, $deleteResponse->getStatusCode());

        $secondCreateResponse = $this->applicationClient->makeCreateFileSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_EMAIL),
            $requestParameters
        );

        self::assertSame(200, $secondCreateResponse->getStatusCode());

        $secondCreateResponseData = json_decode($secondCreateResponse->getBody()->getContents(), true);
        \assert(is_array($secondCreateResponseData));
        self::assertNotSame($sourceId, $secondCreateResponseData['id']);
    }
}
