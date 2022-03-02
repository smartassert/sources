<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\FileSource;
use App\Entity\SourceInterface;
use App\Services\Source\Store;
use App\Tests\DataProvider\UpdateSourceInvalidRequestDataProviderTrait;
use App\Tests\DataProvider\UpdateSourceSuccessDataProviderTrait;
use App\Tests\Model\UserId;
use App\Tests\Services\EntityRemover;

class UpdateSourceTest extends AbstractIntegrationTest
{
    use UpdateSourceInvalidRequestDataProviderTrait;
    use UpdateSourceSuccessDataProviderTrait;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $store = self::getContainer()->get(Store::class);
        \assert($store instanceof Store);
        $this->store = $store;

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
        }
    }

    public function testUpdateInvalidSourceUser(): void
    {
        $source = new FileSource(UserId::create(), '');
        $this->store->add($source);

        $response = $this->applicationClient->makeUpdateSourceRequest($this->validToken, $source->getId(), []);

        $this->responseAsserter->assertForbiddenResponse($response);
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

        $response = $this->applicationClient->makeUpdateSourceRequest($this->validToken, $source->getId(), $payload);

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

        $response = $this->applicationClient->makeUpdateSourceRequest($this->validToken, $source->getId(), $payload);

        $expectedResponseData = $this->sourceUserIdMutator->setSourceDataUserId($expectedResponseData);

        $this->responseAsserter->assertSuccessfulJsonResponse($response, $expectedResponseData);
    }
}
