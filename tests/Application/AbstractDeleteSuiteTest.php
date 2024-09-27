<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\FileSource;
use App\Entity\Suite;
use App\Repository\SourceRepository;
use App\Repository\SuiteRepository;
use App\Request\FileSourceRequest;
use App\Services\EntityIdFactory;
use App\Tests\DataProvider\GetSuiteDataProviderTrait;
use App\Tests\Services\SuiteFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;

abstract class AbstractDeleteSuiteTest extends AbstractApplicationTest
{
    use GetSuiteDataProviderTrait;

    private FileSource $source;
    private SuiteRepository $suiteRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $createSourceResponse = $this->applicationClient->makeCreateFileSourceRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            [
                FileSourceRequest::PARAMETER_LABEL => 'label',
            ]
        );
        $createSourceResponseData = json_decode($createSourceResponse->getBody()->getContents(), true);
        \assert(is_array($createSourceResponseData));
        $sourceId = $createSourceResponseData['id'] ?? null;
        \assert(is_string($sourceId));

        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);
        $source = $sourceRepository->find($sourceId);
        \assert($source instanceof FileSource);
        $this->source = $source;

        $suiteRepository = self::getContainer()->get(SuiteRepository::class);
        \assert($suiteRepository instanceof SuiteRepository);
        $this->suiteRepository = $suiteRepository;
    }

    public function testDeleteSuiteSuiteNotFound(): void
    {
        $response = $this->applicationClient->makeDeleteSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            (new EntityIdFactory())->create()
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    /**
     * @param callable(FileSource): Suite   $suiteCreator
     * @param callable(Suite): array<mixed> $expectedResponseDataCreator
     */
    #[DataProvider('getSuiteDataProvider')]
    public function testDeleteSuccess(callable $suiteCreator, callable $expectedResponseDataCreator): void
    {
        $suite = $suiteCreator($this->source);
        $this->suiteRepository->save($suite);
        $suiteId = $suite->id;

        $response = $this->applicationClient->makeDeleteSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $suite->id,
        );

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        \assert($entityManager instanceof EntityManagerInterface);

        $entityManager->clear();
        $suite = $this->suiteRepository->find($suiteId);
        \assert($suite instanceof Suite);

        $expectedResponseData = $expectedResponseDataCreator($suite);

        $this->responseAsserter->assertSuccessfulJsonResponse($response, $expectedResponseData);
    }

    public function testDeleteIsIdempotent(): void
    {
        $suite = SuiteFactory::create($this->source);
        $this->suiteRepository->save($suite);

        $deletedAt = new \DateTimeImmutable('1978-05-02');
        $suite->setDeletedAt($deletedAt);
        $this->suiteRepository->save($suite);

        $response = $this->applicationClient->makeDeleteSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $suite->id
        );

        $responseData = json_decode($response->getBody()->getContents(), true);
        \assert(is_array($responseData));

        self::assertSame((int) $deletedAt->format('U'), $responseData['deleted_at']);
    }
}
