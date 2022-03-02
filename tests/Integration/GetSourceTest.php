<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\FileSource;
use App\Entity\SourceInterface;
use App\Model\EntityId;
use App\Services\Source\Store;
use App\Tests\DataProvider\GetSourceSuccessDataProviderTrait;
use App\Tests\Model\UserId;
use App\Tests\Services\EntityRemover;

class GetSourceTest extends AbstractIntegrationTest
{
    use GetSourceSuccessDataProviderTrait;

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

    public function testGetSourceNotFound(): void
    {
        $response = $this->applicationClient->makeGetSourceRequest($this->validToken, EntityId::create());

        $this->responseAsserter->assertNotFoundResponse($response);
    }

    public function testGetInvalidSourceUser(): void
    {
        $source = new FileSource(UserId::create(), '');
        $this->store->add($source);

        $response = $this->applicationClient->makeGetSourceRequest($this->validToken, $source->getId());

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    /**
     * @dataProvider getSourceSuccessDataProvider
     *
     * @param array<mixed> $expectedResponseData
     */
    public function testGetSuccess(SourceInterface $source, array $expectedResponseData): void
    {
        $source = $this->sourceUserIdMutator->setSourceUserId($source);
        $this->store->add($source);

        $response = $this->applicationClient->makeGetSourceRequest($this->validToken, $source->getId());

        $expectedResponseData = $this->sourceUserIdMutator->setSourceDataUserId($expectedResponseData);

        $this->responseAsserter->assertSuccessfulJsonResponse($response, $expectedResponseData);
    }
}
