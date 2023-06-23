<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\SourceInterface;
use App\Enum\Source\Type;
use App\Repository\SourceRepository;
use App\Tests\Services\SourceOriginFactory;
use SmartAssert\TestAuthenticationProviderBundle\UserProvider;

abstract class AbstractListSourcesTest extends AbstractApplicationTest
{
    /**
     * @dataProvider listSuccessDataProvider
     *
     * @param callable(UserProvider): SourceInterface[] $sourcesCreator
     * @param callable(SourceInterface[] $sources): array<mixed> $expectedResponseDataCreator
     */
    public function testListSuccess(
        callable $sourcesCreator,
        callable $expectedResponseDataCreator,
    ): void {
        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);

        $sources = $sourcesCreator(self::$users);
        foreach ($sources as $source) {
            $sourceRepository->save($source);
        }

        $expectedResponseData = $expectedResponseDataCreator($sources);

        $response = $this->applicationClient->makeListSourcesRequest(
            self::$apiTokens->get(self::USER_1_EMAIL)
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
    public static function listSuccessDataProvider(): array
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
            'file and git, no user match' => [
                'sourcesCreator' => function () {
                    return [
                        SourceOriginFactory::create(type: 'file'),
                        SourceOriginFactory::create(type: 'git'),
                    ];
                },
                'expectedResponseDataCreator' => function () {
                    return [];
                },
            ],
            'has file and git sources for correct user only' => [
                'sourcesCreator' => function (UserProvider $users) {
                    $fileSource = SourceOriginFactory::create(
                        type: 'file',
                        userId: $users->get(self::USER_1_EMAIL)->id,
                    );
                    $gitSource = SourceOriginFactory::create(
                        type: 'git',
                        userId: $users->get(self::USER_1_EMAIL)->id,
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
            'has file and git sources for mixed users' => [
                'sourcesCreator' => function (UserProvider $users) {
                    return [
                        SourceOriginFactory::create(
                            type: 'file',
                            userId: $users->get(self::USER_1_EMAIL)->id,
                        ),
                        SourceOriginFactory::create(type: 'file'),
                        SourceOriginFactory::create(type: 'file'),
                        SourceOriginFactory::create(
                            type: 'git',
                            userId: $users->get(self::USER_1_EMAIL)->id,
                        ),
                        SourceOriginFactory::create(type: 'git'),
                        SourceOriginFactory::create(type: 'git'),
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
