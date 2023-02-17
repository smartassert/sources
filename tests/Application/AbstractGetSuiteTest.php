<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\FileSource;
use App\Entity\Suite;
use App\Repository\FileSourceRepository;
use App\Repository\SuiteRepository;
use App\Request\FileSourceRequest;
use App\Services\EntityIdFactory;

abstract class AbstractGetSuiteTest extends AbstractApplicationTest
{
    private FileSource $source;
    private SuiteRepository $suiteRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $createSourceResponse = $this->applicationClient->makeCreateFileSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(),
            [
                FileSourceRequest::PARAMETER_LABEL => 'label',
            ]
        );
        $createSourceResponseData = json_decode($createSourceResponse->getBody()->getContents(), true);
        \assert(is_array($createSourceResponseData));
        $sourceId = $createSourceResponseData['id'] ?? null;
        \assert(is_string($sourceId));

        $fileSourceRepository = self::getContainer()->get(FileSourceRepository::class);
        \assert($fileSourceRepository instanceof FileSourceRepository);
        $source = $fileSourceRepository->find($sourceId);
        \assert($source instanceof FileSource);
        $this->source = $source;

        $suiteRepository = self::getContainer()->get(SuiteRepository::class);
        \assert($suiteRepository instanceof SuiteRepository);
        $this->suiteRepository = $suiteRepository;
    }

    public function testGetSuiteSourceNotFound(): void
    {
        $response = $this->applicationClient->makeGetSuiteRequest(
            self::$authenticationConfiguration->getValidApiToken(),
            (new EntityIdFactory())->create(),
            (new EntityIdFactory())->create()
        );

        $this->responseAsserter->assertNotFoundResponse($response);
    }

    public function testGetSuiteSuiteNotFound(): void
    {
        $response = $this->applicationClient->makeGetSuiteRequest(
            self::$authenticationConfiguration->getValidApiToken(),
            $this->source->getId(),
            (new EntityIdFactory())->create()
        );

        $this->responseAsserter->assertNotFoundResponse($response);
    }

    /**
     * @dataProvider getSuccessDataProvider
     *
     * @param callable(FileSource): Suite $suiteCreator
     * @param array<mixed>                $expected
     */
    public function testGetSuccess(callable $suiteCreator, array $expected): void
    {
        $suite = $suiteCreator($this->source);
        $this->suiteRepository->save($suite);

        $response = $this->applicationClient->makeGetSuiteRequest(
            self::$authenticationConfiguration->getValidApiToken(),
            $this->source->getId(),
            $suite->id,
        );

        $this->responseAsserter->assertSuccessfulJsonResponse(
            $response,
            array_merge(
                [
                    'id' => $suite->id,
                    'source_id' => $this->source->getId(),
                ],
                $expected
            )
        );
    }

    /**
     * @return array<mixed>
     */
    public function getSuccessDataProvider(): array
    {
        $label = md5((string) rand());
        $tests = [
            md5((string) rand()) . '.yaml',
            md5((string) rand()) . '.yml',
            md5((string) rand()) . '.yaml',
        ];

        return [
            'default' => [
                'suiteCreator' => function (FileSource $source) use ($label, $tests) {
                    return new Suite(
                        (new EntityIdFactory())->create(),
                        $source,
                        $label,
                        $tests,
                    );
                },
                'expected' => [
                    'label' => $label,
                    'tests' => $tests,
                ],
            ],
        ];
    }
}
