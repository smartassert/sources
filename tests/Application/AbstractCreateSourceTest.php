<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\SourceInterface;
use App\Repository\SourceRepository;
use App\Tests\DataProvider\CreateSourceInvalidRequestDataProviderTrait;
use App\Tests\DataProvider\CreateSourceSuccessDataProviderTrait;
use App\Tests\Services\EntityRemover;

abstract class AbstractCreateSourceTest extends AbstractApplicationTest
{
    use CreateSourceInvalidRequestDataProviderTrait;
    use CreateSourceSuccessDataProviderTrait;

    /**
     * @dataProvider createSourceInvalidRequestDataProvider
     *
     * @param array<string, string> $requestParameters
     * @param array<string, string> $expectedResponseData
     */
    public function testCreateInvalidSourceRequest(array $requestParameters, array $expectedResponseData): void
    {
        $response = $this->applicationClient->makeCreateSourceRequest(
            $this->authenticationConfiguration->validToken,
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
        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
        }

        $response = $this->applicationClient->makeCreateSourceRequest(
            $this->authenticationConfiguration->validToken,
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
        $expected['user_id'] = $this->authenticationConfiguration->authenticatedUserId;

        $this->responseAsserter->assertSuccessfulJsonResponse($response, $expected);
    }
}
