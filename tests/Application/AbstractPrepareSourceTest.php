<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\FileSource;
use App\Entity\RunSource;
use App\Entity\SourceOriginInterface;
use App\Enum\RunSource\State;
use App\Enum\Source\Type;
use App\Repository\RunSourceRepository;
use App\Tests\Services\SourceProvider;

abstract class AbstractPrepareSourceTest extends AbstractApplicationTest
{
    private RunSourceRepository $runSourceRepository;
    private SourceProvider $sourceProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $runSourceRepository = self::getContainer()->get(RunSourceRepository::class);
        \assert($runSourceRepository instanceof RunSourceRepository);
        $this->runSourceRepository = $runSourceRepository;

        $sourceProvider = self::getContainer()->get(SourceProvider::class);
        \assert($sourceProvider instanceof SourceProvider);
        $this->sourceProvider = $sourceProvider;
    }

    public function testPrepareRunSource(): void
    {
        $fileSource = new FileSource($this->authenticationConfiguration->authenticatedUserId, 'file source label');
        $source = new RunSource($fileSource);

        $this->store->add($source);

        $response = $this->applicationClient->makePrepareSourceRequest(
            $this->authenticationConfiguration->validToken,
            $source->getId(),
            []
        );

        $this->responseAsserter->assertNotFoundResponse($response);
    }

    /**
     * @dataProvider prepareSuccessDataProvider
     *
     * @param array<string, string> $payload
     * @param array<string, string> $expectedResponseParameters
     */
    public function testPrepareSuccess(
        string $sourceIdentifier,
        array $payload,
        array $expectedResponseParameters,
    ): void {
        $this->sourceProvider->initialize([$sourceIdentifier]);
        $source = $this->sourceProvider->get($sourceIdentifier);
        self::assertInstanceOf(SourceOriginInterface::class, $source);

        $response = $this->applicationClient->makePrepareSourceRequest(
            $this->authenticationConfiguration->validToken,
            $source->getId(),
            $payload
        );

        $runSource = $this->runSourceRepository->findByParent($source);
        self::assertInstanceOf(RunSource::class, $runSource);

        $expectedResponseData = [
            'id' => $runSource->getId(),
            'user_id' => $runSource->getUserId(),
            'parent' => $source->getId(),
            'type' => Type::RUN->value,
            'parameters' => $expectedResponseParameters,
            'state' => State::REQUESTED->value,
        ];

        $this->responseAsserter->assertPrepareSourceSuccessResponse($response, $expectedResponseData);
    }

    /**
     * @return array<mixed>
     */
    public function prepareSuccessDataProvider(): array
    {
        return [
            Type::FILE->value => [
                'sourceIdentifier' => SourceProvider::FILE_WITHOUT_RUN_SOURCE,
                'payload' => [],
                'expectedResponseParameters' => [],
            ],
            Type::GIT->value => [
                'sourceIdentifier' => SourceProvider::GIT_WITHOUT_CREDENTIALS_WITHOUT_RUN_SOURCE,
                'payload' => [],
                'expectedResponseParameters' => [],
            ],
            Type::GIT->value . ' with ref request parameters' => [
                'sourceIdentifier' => SourceProvider::GIT_WITHOUT_CREDENTIALS_WITHOUT_RUN_SOURCE,
                'payload' => [
                    'ref' => 'v1.1',
                ],
                'expectedResponseParameters' => [
                    'ref' => 'v1.1',
                ],
            ],
            Type::GIT->value . ' with request parameters including ref' => [
                'sourceIdentifier' => SourceProvider::GIT_WITHOUT_CREDENTIALS_WITHOUT_RUN_SOURCE,
                'payload' => [
                    'ref' => 'v1.1',
                    'ignored1' => 'value',
                    'ignored2' => 'value',
                ],
                'expectedResponseParameters' => [
                    'ref' => 'v1.1',
                ],
            ],
        ];
    }
}
