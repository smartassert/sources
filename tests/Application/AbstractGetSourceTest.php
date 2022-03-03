<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\SourceInterface;
use App\Model\EntityId;
use App\Tests\DataProvider\GetSourceSuccessDataProviderTrait;
use App\Tests\Services\SourceUserIdMutator;

abstract class AbstractGetSourceTest extends AbstractApplicationTest
{
    use GetSourceSuccessDataProviderTrait;

    public function testGetSourceNotFound(): void
    {
        $response = $this->applicationClient->makeGetSourceRequest(
            $this->authenticationConfiguration->validToken,
            EntityId::create()
        );

        $this->responseAsserter->assertNotFoundResponse($response);
    }

    /**
     * @dataProvider getSourceSuccessDataProvider
     *
     * @param array<mixed> $expectedResponseData
     */
    public function testGetSuccess(SourceInterface $source, array $expectedResponseData): void
    {
        $sourceUserIdMutator = self::getContainer()->get(SourceUserIdMutator::class);
        \assert($sourceUserIdMutator instanceof SourceUserIdMutator);
        $source = $sourceUserIdMutator->setSourceUserId($source);
        $this->store->add($source);

        $response = $this->applicationClient->makeGetSourceRequest(
            $this->authenticationConfiguration->validToken,
            $source->getId()
        );

        $expectedResponseData = $sourceUserIdMutator->setSourceDataUserId($expectedResponseData);

        $this->responseAsserter->assertSuccessfulJsonResponse($response, $expectedResponseData);
    }
}
