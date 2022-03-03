<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Enum\RunSource\State;
use App\Enum\Source\Type;
use App\Repository\RunSourceRepository;
use App\Tests\Application\AbstractPrepareSourceTest;
use App\Tests\Services\SourceUserIdMutator;

class PrepareSourceTest extends AbstractPrepareSourceTest
{
    use GetClientAdapterTrait;

    private SourceUserIdMutator $sourceUserIdMutator;
    private RunSourceRepository $runSourceRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $sourceUserIdMutator = self::getContainer()->get(SourceUserIdMutator::class);
        \assert($sourceUserIdMutator instanceof SourceUserIdMutator);
        $this->sourceUserIdMutator = $sourceUserIdMutator;

        $runSourceRepository = self::getContainer()->get(RunSourceRepository::class);
        \assert($runSourceRepository instanceof RunSourceRepository);
        $this->runSourceRepository = $runSourceRepository;
    }

    /**
     * @dataProvider prepareSuccessDataProvider
     *
     * @param array<string, string> $payload
     * @param array<mixed>          $expectedResponseData
     */
    public function testPrepareSuccess(
        FileSource|GitSource $source,
        array $payload,
        array $expectedResponseData
    ): void {
        $this->sourceUserIdMutator->setSourceUserId($source);
        $this->store->add($source);

        $response = $this->applicationClient->makePrepareSourceRequest(
            $this->authenticationConfiguration->validToken,
            $source->getId(),
            $payload
        );

        $runSource = $this->runSourceRepository->findByParent($source);
        self::assertInstanceOf(RunSource::class, $runSource);

        $expectedResponseData = $this->sourceUserIdMutator->setSourceDataUserId($expectedResponseData);
        $expectedResponseData['id'] = $runSource->getId();

        $this->responseAsserter->assertPrepareSourceSuccessResponse($response, $expectedResponseData);
    }

    /**
     * @return array<mixed>
     */
    public function prepareSuccessDataProvider(): array
    {
        $userId = SourceUserIdMutator::AUTHENTICATED_USER_ID_PLACEHOLDER;

        $fileSource = new FileSource($userId, 'file source label');
        $gitSource = new GitSource($userId, 'https://example.com/repository.git', '/', md5((string) rand()));

        return [
            Type::FILE->value => [
                'source' => $fileSource,
                'payload' => [],
                'expectedResponseData' => [
                    'id' => '{{ runSourceId }}',
                    'user_id' => $gitSource->getUserId(),
                    'type' => Type::RUN->value,
                    'parent' => $fileSource->getId(),
                    'parameters' => [],
                    'state' => State::REQUESTED->value,
                ],
            ],
            Type::GIT->value => [
                'source' => $gitSource,
                'payload' => [],
                'expectedResponseData' => [
                    'id' => '{{ runSourceId }}',
                    'user_id' => $gitSource->getUserId(),
                    'type' => Type::RUN->value,
                    'parent' => $gitSource->getId(),
                    'parameters' => [],
                    'state' => State::REQUESTED->value,
                ],
            ],
            Type::GIT->value . ' with ref request parameters' => [
                'source' => $gitSource,
                'payload' => [
                    'ref' => 'v1.1',
                ],
                'expectedResponseData' => [
                    'id' => '{{ runSourceId }}',
                    'user_id' => $gitSource->getUserId(),
                    'type' => Type::RUN->value,
                    'parent' => $gitSource->getId(),
                    'parameters' => [
                        'ref' => 'v1.1',
                    ],
                    'state' => State::REQUESTED->value,
                ],
            ],
            Type::GIT->value . ' with request parameters including ref' => [
                'source' => $gitSource,
                'payload' => [
                    'ref' => 'v1.1',
                    'ignored1' => 'value',
                    'ignored2' => 'value',
                ],
                'expectedResponseData' => [
                    'id' => '{{ runSourceId }}',
                    'user_id' => $gitSource->getUserId(),
                    'type' => Type::RUN->value,
                    'parent' => $gitSource->getId(),
                    'parameters' => [
                        'ref' => 'v1.1',
                    ],
                    'state' => State::REQUESTED->value,
                ],
            ],
        ];
    }
}
