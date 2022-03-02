<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Enum\RunSource\State;
use App\Enum\Source\Type;
use App\Model\EntityId;
use App\Repository\RunSourceRepository;
use App\Repository\SourceRepository;
use App\Request\GitSourceRequest;
use App\Request\SourceRequestInterface;
use App\Services\RunSourceSerializer;
use App\Services\Source\Store;
use App\Tests\DataProvider\DeleteSourceSuccessDataProviderTrait;
use App\Tests\DataProvider\GetSourceSuccessDataProviderTrait;
use App\Tests\DataProvider\TestConstants;
use App\Tests\DataProvider\UpdateSourceSuccessDataProviderTrait;
use App\Tests\Model\UserId;
use App\Tests\Services\EntityRemover;
use App\Tests\Services\FileStoreFixtureCreator;
use League\Flysystem\FilesystemOperator;

class UserSourceControllerTest extends AbstractSourceControllerTest
{
    use DeleteSourceSuccessDataProviderTrait;
    use GetSourceSuccessDataProviderTrait;
    use UpdateSourceSuccessDataProviderTrait;

    private SourceRepository $sourceRepository;
    private RunSourceRepository $runSourceRepository;
    private Store $store;
    private FileStoreFixtureCreator $fixtureCreator;
    private FilesystemOperator $runSourceStorage;
    private FilesystemOperator $fileSourceStorage;
    private FilesystemOperator $fixtureStorage;

    protected function setUp(): void
    {
        parent::setUp();

        $store = self::getContainer()->get(Store::class);
        \assert($store instanceof Store);
        $this->store = $store;

        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);
        $this->sourceRepository = $sourceRepository;

        $runSourceRepository = self::getContainer()->get(RunSourceRepository::class);
        \assert($runSourceRepository instanceof RunSourceRepository);
        $this->runSourceRepository = $runSourceRepository;

        $fixtureCreator = self::getContainer()->get(FileStoreFixtureCreator::class);
        \assert($fixtureCreator instanceof FileStoreFixtureCreator);
        $this->fixtureCreator = $fixtureCreator;

        $runSourceStorage = self::getContainer()->get('run_source.storage');
        \assert($runSourceStorage instanceof FilesystemOperator);
        $this->runSourceStorage = $runSourceStorage;

        $fileSourceStorage = self::getContainer()->get('file_source.storage');
        \assert($fileSourceStorage instanceof FilesystemOperator);
        $this->fileSourceStorage = $fileSourceStorage;

        $fixtureStorage = self::getContainer()->get('test_fixtures.storage');
        \assert($fixtureStorage instanceof FilesystemOperator);
        $this->fixtureStorage = $fixtureStorage;

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
        }
    }

    public function testGetUnauthorizedUser(): void
    {
        $response = $this->application->makeGetSourceRequest($this->invalidToken, EntityId::create());

        $this->responseAsserter->assertUnauthorizedResponse($response);
        $this->requestAsserter->assertAuthorizationRequestIsMade($this->invalidToken);
    }

    public function testGetSourceNotFound(): void
    {
        $response = $this->application->makeGetSourceRequest($this->validToken, EntityId::create());

        $this->responseAsserter->assertNotFoundResponse($response);
    }

    public function testGetInvalidSourceUser(): void
    {
        $source = new FileSource(UserId::create(), '');
        $this->store->add($source);

        $response = $this->application->makeGetSourceRequest($this->validToken, $source->getId());

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    /**
     * @dataProvider getSourceSuccessDataProvider
     *
     * @param array<mixed> $expectedResponseData
     */
    public function testGetSuccess(SourceInterface $source, array $expectedResponseData): void
    {
        $source = $this->sourceUserIdMutator->setSourceUserId($source);
        $this->store->add($source);

        $response = $this->application->makeGetSourceRequest($this->validToken, $source->getId());

        $expectedResponseData = $this->sourceUserIdMutator->setSourceDataUserId($expectedResponseData);

        $this->responseAsserter->assertSuccessfulJsonResponse($response, $expectedResponseData);
        $this->requestAsserter->assertAuthorizationRequestIsMade();
    }

    public function testUpdateUnauthorizedUser(): void
    {
        $response = $this->application->makeUpdateSourceRequest($this->invalidToken, EntityId::create(), []);

        $this->responseAsserter->assertUnauthorizedResponse($response);
        $this->requestAsserter->assertAuthorizationRequestIsMade($this->invalidToken);
    }

    public function testUpdateInvalidSourceUser(): void
    {
        $source = new FileSource(UserId::create(), '');
        $this->store->add($source);

        $response = $this->application->makeUpdateSourceRequest($this->validToken, $source->getId(), []);

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    /**
     * @dataProvider updateInvalidRequestDataProvider
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

        $response = $this->application->makeUpdateSourceRequest($this->validToken, $source->getId(), $payload);

        $expectedResponseData = $this->sourceUserIdMutator->setSourceDataUserId($expectedResponseData);

        $this->responseAsserter->assertInvalidRequestJsonResponse($response, $expectedResponseData);
        $this->requestAsserter->assertAuthorizationRequestIsMade();
    }

    /**
     * @return array<mixed>
     */
    public function updateInvalidRequestDataProvider(): array
    {
        $userId = TestConstants::AUTHENTICATED_USER_ID_PLACEHOLDER;
        $hostUrl = 'https://example.com/repository.git';
        $path = '/';
        $credentials = md5((string) rand());

        $gitSource = new GitSource($userId, $hostUrl, $path, $credentials);

        return [
            Type::GIT->value . ' missing host url' => [
                'source' => $gitSource,
                'payload' => [
                    SourceRequestInterface::PARAMETER_TYPE => Type::GIT->value,
                    GitSourceRequest::PARAMETER_HOST_URL => '',
                    GitSourceRequest::PARAMETER_PATH => $path,
                ],
                'expectedResponseData' => [
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            'host-url' => [
                                'value' => '',
                                'message' => 'This value should not be blank.',
                            ],
                        ],
                    ],
                ],
            ],
        ];
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

        $response = $this->application->makeUpdateSourceRequest($this->validToken, $source->getId(), $payload);

        $expectedResponseData = $this->sourceUserIdMutator->setSourceDataUserId($expectedResponseData);

        $this->responseAsserter->assertSuccessfulJsonResponse($response, $expectedResponseData);
        $this->requestAsserter->assertAuthorizationRequestIsMade();
    }

    public function testDeleteUnauthorizedUser(): void
    {
        $response = $this->application->makeDeleteSourceRequest($this->invalidToken, EntityId::create());

        $this->responseAsserter->assertUnauthorizedResponse($response);
        $this->requestAsserter->assertAuthorizationRequestIsMade($this->invalidToken);
    }

    public function testDeleteInvalidSourceUser(): void
    {
        $source = new FileSource(UserId::create(), '');
        $this->store->add($source);

        $response = $this->application->makeDeleteSourceRequest($this->validToken, $source->getId());

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    /**
     * @dataProvider deleteSourceSuccessDataProvider
     */
    public function testDeleteSuccess(SourceInterface $source, int $expectedRepositoryCount): void
    {
        $this->sourceUserIdMutator->setSourceUserId($source);

        $this->store->add($source);
        self::assertGreaterThan(0, $this->sourceRepository->count([]));

        $response = $this->application->makeDeleteSourceRequest($this->validToken, $source->getId());

        $this->responseAsserter->assertSuccessfulResponseWithNoBody($response);
        $this->requestAsserter->assertAuthorizationRequestIsMade();
        self::assertSame($expectedRepositoryCount, $this->sourceRepository->count([]));
    }

    public function testDeleteRunSourceDeletesRunSourceFiles(): void
    {
        $fileSource = new FileSource($this->authenticationConfiguration->authenticatedUserId, 'file source label');
        $runSource = new RunSource($fileSource);

        $this->store->add($fileSource);
        $this->store->add($runSource);

        $serializedRunSourcePath = $runSource->getDirectoryPath() . '/' . RunSourceSerializer::SERIALIZED_FILENAME;

        $this->runSourceStorage->write($serializedRunSourcePath, '- serialized content');

        self::assertTrue($this->runSourceStorage->directoryExists($runSource->getDirectoryPath()));
        self::assertTrue($this->runSourceStorage->fileExists($serializedRunSourcePath));

        $response = $this->application->makeDeleteSourceRequest($this->validToken, $runSource->getId());

        $this->responseAsserter->assertSuccessfulResponseWithNoBody($response);
        self::assertFalse($this->runSourceStorage->directoryExists($runSource->getDirectoryPath()));
        self::assertFalse($this->runSourceStorage->fileExists($serializedRunSourcePath));
    }

    public function testDeleteFileSourceDeletesFileSourceFiles(): void
    {
        $fileSource = new FileSource($this->authenticationConfiguration->authenticatedUserId, 'file source label');
        $filename = 'file.yaml';

        $this->store->add($fileSource);

        $sourceRelativePath = $fileSource->getDirectoryPath();
        $fileRelativePath = $sourceRelativePath . '/' . $filename;

        $this->fileSourceStorage->write($fileRelativePath, '- content');

        self::assertTrue($this->fileSourceStorage->directoryExists($sourceRelativePath));
        self::assertTrue($this->fileSourceStorage->fileExists($fileRelativePath));

        $response = $this->application->makeDeleteSourceRequest($this->validToken, $fileSource->getId());

        $this->responseAsserter->assertSuccessfulResponseWithNoBody($response);
        self::assertSame(0, $this->sourceRepository->count([]));

        self::assertFalse($this->fileSourceStorage->directoryExists($sourceRelativePath));
        self::assertFalse($this->fileSourceStorage->fileExists($fileRelativePath));
    }

    public function testPrepareUnauthorizedUser(): void
    {
        $response = $this->application->makePrepareSourceRequest($this->invalidToken, EntityId::create(), []);

        $this->responseAsserter->assertUnauthorizedResponse($response);
        $this->requestAsserter->assertAuthorizationRequestIsMade($this->invalidToken);
    }

    public function testPrepareInvalidSourceUser(): void
    {
        $source = new FileSource(UserId::create(), '');
        $this->store->add($source);

        $response = $this->application->makePrepareSourceRequest($this->validToken, $source->getId(), []);

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testPrepareRunSource(): void
    {
        $fileSource = new FileSource($this->authenticationConfiguration->authenticatedUserId, 'file source label');
        $source = new RunSource($fileSource);

        $this->store->add($source);

        $response = $this->application->makePrepareSourceRequest($this->validToken, $source->getId(), []);

        $this->responseAsserter->assertNotFoundResponse($response);
        $this->requestAsserter->assertAuthorizationRequestIsMade();
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

        $response = $this->application->makePrepareSourceRequest($this->validToken, $source->getId(), $payload);

        $runSource = $this->runSourceRepository->findByParent($source);
        self::assertInstanceOf(RunSource::class, $runSource);

        $expectedResponseData = $this->sourceUserIdMutator->setSourceDataUserId($expectedResponseData);
        $expectedResponseData['id'] = $runSource->getId();

        $this->responseAsserter->assertPrepareSourceSuccessResponse($response, $expectedResponseData);
        $this->requestAsserter->assertAuthorizationRequestIsMade();
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

        $response = $this->application->makeReadSourceRequest($this->validToken, $runSource->getId());

        $this->responseAsserter->assertReadSourceSuccessResponse(
            $response,
            trim($this->fixtureStorage->read($serializedRunSourceFixturePath))
        );
    }
}
