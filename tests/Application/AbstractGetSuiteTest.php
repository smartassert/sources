<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\FileSource;
use App\Entity\Suite;
use App\Enum\Source\Type;
use App\Repository\FileSourceRepository;
use App\Repository\SuiteRepository;
use App\Request\FileSourceRequest;
use App\Request\OriginSourceRequest;
use App\Services\EntityIdFactory;

abstract class AbstractGetSuiteTest extends AbstractApplicationTest
{
    private FileSource $source;
    private SuiteRepository $suiteRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $createSourceResponse = $this->applicationClient->makeCreateSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            [
                OriginSourceRequest::PARAMETER_TYPE => Type::FILE->value,
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
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            (new EntityIdFactory())->create(),
            (new EntityIdFactory())->create()
        );

        $this->responseAsserter->assertNotFoundResponse($response);
    }

    public function testGetSuiteSuiteNotFound(): void
    {
        $response = $this->applicationClient->makeGetSuiteRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
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
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
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
                    $suite = new Suite((new EntityIdFactory())->create(), $source);
                    $suite->setLabel($label);
                    $suite->setTests($tests);

                    return $suite;
                },
                'expected' => [
                    'label' => $label,
                    'tests' => $tests,
                ],
            ],
        ];
    }
}
