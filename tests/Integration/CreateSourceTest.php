<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\SourceInterface;
use App\Repository\SourceRepository;
use App\Tests\DataProvider\CreateSourceInvalidRequestDataProviderTrait;
use App\Tests\DataProvider\CreateSourceSuccessDataProviderTrait;
use App\Tests\Services\EntityRemover;

class CreateSourceTest extends AbstractIntegrationTest
{
    use CreateSourceInvalidRequestDataProviderTrait;
    use CreateSourceSuccessDataProviderTrait;

    private SourceRepository $sourceRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);
        $this->sourceRepository = $sourceRepository;

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
        }
    }

    public function testCreateUnauthorizedUser(): void
    {
        $response = $this->client->makeCreateSourceRequest($this->invalidToken, []);

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider createSourceInvalidRequestDataProvider
     *
     * @param array<string, string> $requestParameters
     * @param array<string, string> $expectedResponseData
     */
    public function testCreateInvalidSourceRequest(array $requestParameters, array $expectedResponseData): void
    {
        $response = $this->client->makeCreateSourceRequest($this->validToken, $requestParameters);

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
        $response = $this->client->makeCreateSourceRequest($this->validToken, $requestParameters);

        $sources = $this->sourceRepository->findAll();
        self::assertIsArray($sources);
        self::assertCount(1, $sources);

        $source = $sources[0];
        self::assertInstanceOf(SourceInterface::class, $source);

        $expected['id'] = $source->getId();
        $expected['user_id'] = $this->authenticationConfiguration->authenticatedUserId;

        $this->responseAsserter->assertSuccessfulJsonResponse($response, $expected);
    }
}
