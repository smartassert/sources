<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\SourceInterface;
use App\Services\Source\Store;
use App\Tests\DataProvider\UpdateSourceInvalidRequestDataProviderTrait;
use App\Tests\DataProvider\UpdateSourceSuccessDataProviderTrait;
use App\Tests\Services\EntityRemover;
use App\Tests\Services\SourceUserIdMutator;

abstract class AbstractUpdateSourceTest extends AbstractApplicationTest
{
    use UpdateSourceInvalidRequestDataProviderTrait;
    use UpdateSourceSuccessDataProviderTrait;

    private Store $store;
    private SourceUserIdMutator $sourceUserIdMutator;

    protected function setUp(): void
    {
        parent::setUp();

        $store = self::getContainer()->get(Store::class);
        \assert($store instanceof Store);
        $this->store = $store;

        $sourceUserIdMutator = self::getContainer()->get(SourceUserIdMutator::class);
        \assert($sourceUserIdMutator instanceof SourceUserIdMutator);
        $this->sourceUserIdMutator = $sourceUserIdMutator;

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
        }
    }

    /**
     * @dataProvider updateSourceInvalidRequestDataProvider
     *
     * @param array<string, string> $payload
     * @param array<mixed>          $expectedResponseData
     */
    public function testUpdateInvalidRequest(
        SourceInterface $source,
        array $payload,
        array $expectedResponseData
    ): void {
        $this->sourceUserIdMutator->setSourceUserId($source);
        $this->store->add($source);

        $response = $this->applicationClient->makeUpdateSourceRequest(
            $this->authenticationConfiguration->validToken,
            $source->getId(),
            $payload
        );

        $expectedResponseData = $this->sourceUserIdMutator->setSourceDataUserId($expectedResponseData);

        $this->responseAsserter->assertInvalidRequestJsonResponse($response, $expectedResponseData);
    }

    /**
     * @dataProvider updateSourceSuccessDataProvider
     *
     * @param array<string, string> $payload
     * @param array<mixed>          $expectedResponseData
     */
    public function testUpdateSuccess(
        SourceInterface $source,
        array $payload,
        array $expectedResponseData
    ): void {
        $this->sourceUserIdMutator->setSourceUserId($source);
        $this->store->add($source);

        $response = $this->applicationClient->makeUpdateSourceRequest(
            $this->authenticationConfiguration->validToken,
            $source->getId(),
            $payload
        );

        $expectedResponseData = $this->sourceUserIdMutator->setSourceDataUserId($expectedResponseData);

        $this->responseAsserter->assertSuccessfulJsonResponse($response, $expectedResponseData);
    }
}
