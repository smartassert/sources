<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Enum\RunSource\State;
use App\Enum\Source\Type;
use App\Repository\RunSourceRepository;
use App\Services\RunSourceSerializer;
use App\Services\Source\Store;
use App\Tests\DataProvider\TestConstants;
use App\Tests\DataProvider\UpdateSourceInvalidRequestDataProviderTrait;
use App\Tests\DataProvider\UpdateSourceSuccessDataProviderTrait;
use App\Tests\Services\EntityRemover;
use App\Tests\Services\FileStoreFixtureCreator;
use League\Flysystem\FilesystemOperator;

class UserSourceControllerTest extends AbstractSourceControllerTest
{
    use UpdateSourceInvalidRequestDataProviderTrait;
    use UpdateSourceSuccessDataProviderTrait;

    private RunSourceRepository $runSourceRepository;
    private Store $store;
    private FileStoreFixtureCreator $fixtureCreator;
    private FilesystemOperator $runSourceStorage;
    private FilesystemOperator $fixtureStorage;

    protected function setUp(): void
    {
        parent::setUp();

        $store = self::getContainer()->get(Store::class);
        \assert($store instanceof Store);
        $this->store = $store;

        $runSourceRepository = self::getContainer()->get(RunSourceRepository::class);
        \assert($runSourceRepository instanceof RunSourceRepository);
        $this->runSourceRepository = $runSourceRepository;

        $fixtureCreator = self::getContainer()->get(FileStoreFixtureCreator::class);
        \assert($fixtureCreator instanceof FileStoreFixtureCreator);
        $this->fixtureCreator = $fixtureCreator;

        $runSourceStorage = self::getContainer()->get('run_source.storage');
        \assert($runSourceStorage instanceof FilesystemOperator);
        $this->runSourceStorage = $runSourceStorage;

        $fixtureStorage = self::getContainer()->get('test_fixtures.storage');
        \assert($fixtureStorage instanceof FilesystemOperator);
        $this->fixtureStorage = $fixtureStorage;

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
        }
    }

    /**
     * @dataProvider updateSourceInvalidRequestDataProvider
     *
     * @param array<string, string> $payload
     * @param array<mixed>          $expectedResponseData
     */
    public function testUpdateInvalidRequest(
        SourceInterface $source,
        array $payload,
        array $expectedResponseData
    ): void {
        $this->sourceUserIdMutator->setSourceUserId($source);
        $this->store->add($source);

        $response = $this->applicationClient->makeUpdateSourceRequest($this->validToken, $source->getId(), $payload);

        $expectedResponseData = $this->sourceUserIdMutator->setSourceDataUserId($expectedResponseData);

        $this->responseAsserter->assertInvalidRequestJsonResponse($response, $expectedResponseData);
    }

    /**
     * @dataProvider updateSourceSuccessDataProvider
     *
     * @param array<string, string> $payload
     * @param array<mixed>          $expectedResponseData
     */
    public function testUpdateSuccess(
        SourceInterface $source,
        array $payload,
        array $expectedResponseData
    ): void {
        $this->sourceUserIdMutator->setSourceUserId($source);
        $this->store->add($source);

        $response = $this->applicationClient->makeUpdateSourceRequest($this->validToken, $source->getId(), $payload);

        $expectedResponseData = $this->sourceUserIdMutator->setSourceDataUserId($expectedResponseData);

        $this->responseAsserter->assertSuccessfulJsonResponse($response, $expectedResponseData);
    }

    public function testPrepareRunSource(): void
    {
        $fileSource = new FileSource($this->authenticationConfiguration->authenticatedUserId, 'file source label');
        $source = new RunSource($fileSource);

        $this->store->add($source);

        $response = $this->applicationClient->makePrepareSourceRequest($this->validToken, $source->getId(), []);

        $this->responseAsserter->assertNotFoundResponse($response);
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

        $response = $this->applicationClient->makePrepareSourceRequest($this->validToken, $source->getId(), $payload);

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
        $userId = TestConstants::AUTHENTICATED_USER_ID_PLACEHOLDER;

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

    public function testReadSuccess(): void
    {
        $serializedRunSourceFixturePath = 'RunSource/source_yml_yaml_entire.yaml';

        $fileSource = new FileSource($this->authenticationConfiguration->authenticatedUserId, 'file source label');
        $runSource = new RunSource($fileSource);
        $this->store->add($runSource);

        $this->fixtureCreator->copyTo(
            $serializedRunSourceFixturePath,
            $this->runSourceStorage,
            $runSource->getDirectoryPath() . '/' . RunSourceSerializer::SERIALIZED_FILENAME
        );

        $response = $this->applicationClient->makeReadSourceRequest($this->validToken, $runSource->getId());

        $this->responseAsserter->assertReadSourceSuccessResponse(
            $response,
            trim($this->fixtureStorage->read($serializedRunSourceFixturePath))
        );
    }
}
