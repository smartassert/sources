<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\SourceInterface;
use App\Repository\SourceRepository;
use App\Services\EntityIdFactory;
use App\Tests\DataProvider\GetSourceDataProviderTrait;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use SmartAssert\TestAuthenticationProviderBundle\UserProvider;

abstract class AbstractGetSourceTest extends AbstractApplicationTest
{
    use GetSourceDataProviderTrait;

    public function testGetSourceNotFound(): void
    {
        $response = $this->applicationClient->makeGetSourceRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            (new EntityIdFactory())->create()
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    /**
     * @param callable(UserProvider): SourceInterface         $sourceCreator
     * @param callable(SourceInterface $source): array<mixed> $expectedResponseDataCreator
     */
    #[DataProvider('getSourceDataProvider')]
    public function testGetSuccess(callable $sourceCreator, callable $expectedResponseDataCreator): void
    {
        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);

        $source = $sourceCreator(self::$users);
        $sourceRepository->save($source);

        $response = $this->applicationClient->makeGetSourceRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $source->getId()
        );

        $expectedResponseData = $expectedResponseDataCreator($source);

        $this->responseAsserter->assertSuccessfulJsonResponse($response, $expectedResponseData);

        $responseData = json_decode($response->getBody()->getContents(), true);
        self::assertIsArray($responseData);
        self::assertArrayNotHasKey('deleted_at', $responseData);
    }

    /**
     * @param callable(UserProvider): SourceInterface         $sourceCreator
     * @param callable(SourceInterface $source): array<mixed> $expectedResponseDataCreator
     */
    #[DataProvider('getSourceDataProvider')]
    public function testGetDeletedSourceSuccess(callable $sourceCreator, callable $expectedResponseDataCreator): void
    {
        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);

        $source = $sourceCreator(self::$users);
        $sourceRepository->save($source);
        $sourceId = $source->getId();

        $this->applicationClient->makeDeleteSourceRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $source->getId()
        );

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        \assert($entityManager instanceof EntityManagerInterface);

        $entityManager->clear();
        $source = $sourceRepository->find($sourceId);
        \assert($source instanceof SourceInterface);

        $response = $this->applicationClient->makeGetSourceRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $source->getId()
        );

        $expectedResponseData = $expectedResponseDataCreator($source);

        $this->responseAsserter->assertSuccessfulJsonResponse($response, $expectedResponseData);
    }
}
