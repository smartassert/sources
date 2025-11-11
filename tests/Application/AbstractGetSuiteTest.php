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
use PHPUnit\Framework\Attributes\DataProvider;

abstract class AbstractGetSuiteTest extends AbstractApplicationTest
{
    use GetSuiteDataProviderTrait;

    private FileSource $source;
    private SuiteRepository $suiteRepository;

    #[\Override]
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

    public function testGetSuiteSuiteNotFound(): void
    {
        $response = $this->applicationClient->makeGetSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            (new EntityIdFactory())->create()
        );

        self::assertSame(403, $response->getStatusCode());
    }

    /**
     * @param callable(FileSource): Suite   $suiteCreator
     * @param callable(Suite): array<mixed> $expectedResponseDataCreator
     */
    #[DataProvider('getSuiteDataProvider')]
    public function testGetSuccess(callable $suiteCreator, callable $expectedResponseDataCreator): void
    {
        $suite = $suiteCreator($this->source);
        $this->suiteRepository->save($suite);

        $response = $this->applicationClient->makeGetSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $suite->getId(),
        );

        $expectedResponseData = $expectedResponseDataCreator($suite);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('content-type'));
        self::assertJsonStringEqualsJsonString(
            (string) json_encode($expectedResponseData),
            $response->getBody()->getContents()
        );
    }
}
