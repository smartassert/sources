<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Enum\Source\Type;
use App\Repository\SourceRepository;
use App\Services\EntityIdFactory;
use App\Tests\Services\AuthenticationConfiguration;
use App\Tests\Services\FileSourceFactory;
use App\Tests\Services\GitSourceFactory;

abstract class AbstractListSourcesTest extends AbstractApplicationTest
{
    /**
     * @dataProvider listSuccessDataProvider
     *
     * @param callable(AuthenticationConfiguration $authenticationConfiguration): SourceInterface[] $sourcesCreator
     * @param callable(SourceInterface[] $sources): array<mixed> $expectedResponseDataCreator
     */
    public function testListSuccess(
        callable $sourcesCreator,
        callable $expectedResponseDataCreator,
    ): void {
        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);

        $sources = $sourcesCreator(self::$authenticationConfiguration);
        foreach ($sources as $source) {
            $sourceRepository->save($source);
        }

        $expectedResponseData = $expectedResponseDataCreator($sources);

        $response = $this->applicationClient->makeListSourcesRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL)
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('content-type'));

        self::assertEquals(
            $expectedResponseData,
            json_decode($response->getBody()->getContents(), true)
        );
    }

    /**
     * @return array<mixed>
     */
    public function listSuccessDataProvider(): array
    {
        return [
            'no sources' => [
                'sourcesCreator' => function () {
                    return [];
                },
                'expectedResponseDataCreator' => function () {
                    return [];
                },
            ],
            'file, git and run sources, no user match' => [
                'sourcesCreator' => function () {
                    $fileSource = FileSourceFactory::create();
                    $gitSource = GitSourceFactory::create();

                    $entityIdFactory = new EntityIdFactory();

                    return [
                        $fileSource,
                        $gitSource,
                        new RunSource($entityIdFactory->create(), $fileSource),
                        new RunSource($entityIdFactory->create(), $gitSource),
                    ];
                },
                'expectedResponseDataCreator' => function () {
                    return [];
                },
            ],
            'has file and git sources for correct user only' => [
                'sourcesCreator' => function (AuthenticationConfiguration $authenticationConfiguration) {
                    $fileSource = FileSourceFactory::create(
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                    );
                    $gitSource = GitSourceFactory::create(
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                    );

                    return [
                        $fileSource,
                        $gitSource,
                    ];
                },
                'expectedResponseDataCreator' => function (array $sources) {
                    $fileSource = $sources[0] ?? null;
                    \assert($fileSource instanceof FileSource);

                    $gitSource = $sources[1] ?? null;
                    \assert($gitSource instanceof GitSource);

                    return [
                        [
                            'id' => $fileSource->getId(),
                            'user_id' => $fileSource->getUserId(),
                            'type' => Type::FILE->value,
                            'label' => $fileSource->getLabel(),
                        ],
                        [
                            'id' => $gitSource->getId(),
                            'user_id' => $gitSource->getUserId(),
                            'type' => Type::GIT->value,
                            'label' => $gitSource->getLabel(),
                            'host_url' => $gitSource->getHostUrl(),
                            'path' => $gitSource->getPath(),
                            'has_credentials' => false,
                        ],
                    ];
                },
            ],
            'has file, git and run sources for correct user only' => [
                'sourcesCreator' => function (AuthenticationConfiguration $authenticationConfiguration) {
                    $fileSource = FileSourceFactory::create(
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                    );
                    $gitSource = GitSourceFactory::create(
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                    );

                    $entityIdFactory = new EntityIdFactory();

                    $fileRunSource = new RunSource($entityIdFactory->create(), $fileSource);
                    $gitRunSource = new RunSource($entityIdFactory->create(), $gitSource);

                    return [
                        $fileSource,
                        $gitSource,
                        $fileRunSource,
                        $gitRunSource,
                    ];
                },
                'expectedResponseDataCreator' => function (array $sources) {
                    $fileSource = $sources[0] ?? null;
                    \assert($fileSource instanceof FileSource);

                    $gitSource = $sources[1] ?? null;
                    \assert($gitSource instanceof GitSource);

                    return [
                        [
                            'id' => $fileSource->getId(),
                            'user_id' => $fileSource->getUserId(),
                            'type' => Type::FILE->value,
                            'label' => $fileSource->getLabel(),
                        ],
                        [
                            'id' => $gitSource->getId(),
                            'user_id' => $gitSource->getUserId(),
                            'type' => Type::GIT->value,
                            'label' => $gitSource->getLabel(),
                            'host_url' => $gitSource->getHostUrl(),
                            'path' => $gitSource->getPath(),
                            'has_credentials' => false,
                        ],
                    ];
                },
            ],
            'has file, git and run sources for mixed users' => [
                'sourcesCreator' => function (AuthenticationConfiguration $authenticationConfiguration) {
                    $fileSource = FileSourceFactory::create(
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                    );
                    $gitSource = GitSourceFactory::create(
                        userId: $authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
                    );

                    $entityIdFactory = new EntityIdFactory();

                    $fileRunSource = new RunSource($entityIdFactory->create(), $fileSource);
                    $gitRunSource = new RunSource($entityIdFactory->create(), $gitSource);

                    return [
                        $fileSource,
                        FileSourceFactory::create(),
                        FileSourceFactory::create(),
                        $gitSource,
                        $fileRunSource,
                        GitSourceFactory::create(),
                        $gitRunSource,
                        GitSourceFactory::create(),
                    ];
                },
                'expectedResponseDataCreator' => function (array $sources) {
                    $fileSource = $sources[0] ?? null;
                    \assert($fileSource instanceof FileSource);

                    $gitSource = $sources[3] ?? null;
                    \assert($gitSource instanceof GitSource);

                    return [
                        [
                            'id' => $fileSource->getId(),
                            'user_id' => $fileSource->getUserId(),
                            'type' => Type::FILE->value,
                            'label' => $fileSource->getLabel(),
                        ],
                        [
                            'id' => $gitSource->getId(),
                            'user_id' => $gitSource->getUserId(),
                            'type' => Type::GIT->value,
                            'label' => $gitSource->getLabel(),
                            'host_url' => $gitSource->getHostUrl(),
                            'path' => $gitSource->getPath(),
                            'has_credentials' => false,
                        ],
                    ];
                },
            ]
        ];
    }
}
