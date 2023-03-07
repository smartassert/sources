<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\SourceInterface;
use App\Repository\SourceRepository;
use App\Services\EntityIdFactory;
use App\Tests\DataProvider\GetSourceDataProviderTrait;
use App\Tests\Services\AuthenticationConfiguration;

abstract class AbstractGetSourceTest extends AbstractApplicationTest
{
    use GetSourceDataProviderTrait;

    public function testGetSourceNotFound(): void
    {
        $response = $this->applicationClient->makeGetSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            (new EntityIdFactory())->create()
        );

        $this->responseAsserter->assertNotFoundResponse($response);
    }

    /**
     * @dataProvider getSourceDataProvider
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

        $responseData = json_decode($response->getBody()->getContents(), true);
        self::assertIsArray($responseData);
        self::assertArrayNotHasKey('deleted_at', $responseData);
    }

    /**
     * @dataProvider getSourceDataProvider
     *
     * @param callable(AuthenticationConfiguration $authenticationConfiguration): SourceInterface $sourceCreator
     */
    public function testGetDeletedSourceSuccess(callable $sourceCreator): void
    {
        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);

        $source = $sourceCreator(self::$authenticationConfiguration);
        $sourceRepository->save($source);

        $unixTimestampBeforeDeletion = time();

        $this->applicationClient->makeDeleteSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $source->getId()
        );

        $unixTimestampAfterDeletion = time();

        $response = $this->applicationClient->makeGetSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $source->getId()
        );

        $responseData = json_decode($response->getBody()->getContents(), true);
        self::assertIsArray($responseData);
        self::assertArrayHasKey('deleted_at', $responseData);

        $deletedAt = $responseData['deleted_at'];
        self::assertIsInt($deletedAt);

        self::assertGreaterThanOrEqual($unixTimestampBeforeDeletion, $deletedAt);
        self::assertLessThanOrEqual($unixTimestampAfterDeletion, $deletedAt);
    }
}
