<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\RunSource;
use App\Entity\SourceOriginInterface;
use App\Enum\RunSource\State;
use App\Enum\Source\Type;
use App\Repository\RunSourceRepository;
use App\Repository\SourceRepository;
use App\Services\EntityIdFactory;
use App\Tests\Services\AuthenticationConfiguration;
use App\Tests\Services\FileSourceFactory;
use App\Tests\Services\GitSourceFactory;
use App\Tests\Services\SourceProvider;

abstract class AbstractPrepareSourceTest extends AbstractApplicationTest
{
    protected SourceProvider $sourceProvider;
    private RunSourceRepository $runSourceRepository;

    private SourceRepository $sourceRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $runSourceRepository = self::getContainer()->get(RunSourceRepository::class);
        \assert($runSourceRepository instanceof RunSourceRepository);
        $this->runSourceRepository = $runSourceRepository;

        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);
        $this->sourceRepository = $sourceRepository;

        $sourceProvider = self::getContainer()->get(SourceProvider::class);
        \assert($sourceProvider instanceof SourceProvider);
        $sourceProvider->setUserId(self::$authenticationConfiguration->getUser(self::USER_1_EMAIL)->id);
        $this->sourceProvider = $sourceProvider;
    }

    public function testPrepareRunSource(): void
    {
        $source = new RunSource(
            (new EntityIdFactory())->create(),
            FileSourceFactory::create(
                self::$authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
            ),
        );

        $this->sourceRepository->save($source);

        $response = $this->applicationClient->makePrepareSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $source->getId(),
            []
        );

        $this->responseAsserter->assertNotFoundResponse($response);
    }

    /**
     * @dataProvider prepareSuccessDataProvider
     *
     * @param callable(AuthenticationConfiguration $authenticationConfiguration): SourceOriginInterface $sourceCreator
     * @param array<string, string> $payload
     * @param array<string, string> $expectedResponseParameters
     */
    public function testPrepareSuccess(
        callable $sourceCreator,
        array $payload,
        array $expectedResponseParameters,
    ): void {
        $source = $sourceCreator(self::$authenticationConfiguration);
        $this->sourceRepository->save($source);

        $response = $this->applicationClient->makePrepareSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $source->getId(),
            $payload
        );

        $runSource = $this->runSourceRepository->findOneBy(['parent' => $source]);
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
                'sourceCreator' => function (AuthenticationConfiguration $authenticationConfiguration) {
                    return FileSourceFactory::create(
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                    );
                },
                'payload' => [],
                'expectedResponseParameters' => [],
            ],
            Type::GIT->value => [
                'sourceCreator' => function (AuthenticationConfiguration $authenticationConfiguration) {
                    return GitSourceFactory::create(
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                    );
                },
                'payload' => [],
                'expectedResponseParameters' => [],
            ],
            Type::GIT->value . ' with ref request parameters' => [
                'sourceCreator' => function (AuthenticationConfiguration $authenticationConfiguration) {
                    return GitSourceFactory::create(
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                    );
                },
                'payload' => [
                    'ref' => 'v1.1',
                ],
                'expectedResponseParameters' => [
                    'ref' => 'v1.1',
                ],
            ],
            Type::GIT->value . ' with request parameters including ref' => [
                'sourceCreator' => function (AuthenticationConfiguration $authenticationConfiguration) {
                    return GitSourceFactory::create(
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                    );
                },
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
