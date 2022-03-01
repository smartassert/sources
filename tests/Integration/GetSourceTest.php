<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Enum\RunSource\FailureReason;
use App\Enum\RunSource\State;
use App\Enum\Source\Type;
use App\Model\EntityId;
use App\Repository\SourceRepository;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use App\Request\InvalidSourceTypeRequest;
use App\Request\SourceRequestInterface;
use App\Services\Source\Store;
use App\Tests\Model\UserId;
use App\Tests\Services\EntityRemover;
use App\Tests\Services\SourceUserIdMutator;

class GetSourceTest extends AbstractIntegrationTest
{
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $store = self::getContainer()->get(Store::class);
        \assert($store instanceof Store);
        $this->store = $store;

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
        }
    }

    public function testGetUnauthorizedUser(): void
    {
        $response = $this->client->makeGetSourceRequest($this->invalidToken, EntityId::create());

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    public function testGetSourceNotFound(): void
    {
        $response = $this->client->makeGetSourceRequest($this->validToken, EntityId::create());

        $this->responseAsserter->assertNotFoundResponse($response);
    }

    public function testGetInvalidSourceUser(): void
    {
        $source = new FileSource(UserId::create(), '');
        $this->store->add($source);

        $response = $this->client->makeGetSourceRequest($this->validToken, $source->getId());

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    /**
     * @dataProvider getSuccessDataProvider
     *
     * @param array<mixed> $expectedResponseData
     */
    public function testGetSuccess(SourceInterface $source, array $expectedResponseData): void
    {
        $source = $this->sourceUserIdMutator->setSourceUserId($source);
        $this->store->add($source);

        $response = $this->client->makeGetSourceRequest($this->validToken, $source->getId());

        $expectedResponseData = $this->sourceUserIdMutator->setSourceDataUserId($expectedResponseData);

        $this->responseAsserter->assertSuccessfulJsonResponse($response, $expectedResponseData);
    }

    /**
     * @return array<mixed>
     */
    public function getSuccessDataProvider(): array
    {
        $userId = SourceUserIdMutator::AUTHENTICATED_USER_ID_PLACEHOLDER;

        $gitSource = new GitSource($userId, 'https://example.com/repository.git', '/', md5((string) rand()));
        $fileSource = new FileSource($userId, 'file source label');
        $runSource = new RunSource($fileSource);

        $failureMessage = 'fatal: repository \'http://example.com/repository.git\' not found';
        $failedRunSource = (new RunSource($gitSource))->setPreparationFailed(
            FailureReason::GIT_CLONE,
            $failureMessage
        );

        return [
            Type::GIT->value => [
                'source' => $gitSource,
                'expectedResponseData' => [
                    'id' => $gitSource->getId(),
                    'user_id' => $gitSource->getUserId(),
                    'type' => Type::GIT->value,
                    'host_url' => $gitSource->getHostUrl(),
                    'path' => $gitSource->getPath(),
                    'has_credentials' => true,
                ],
            ],
            Type::FILE->value => [
                'source' => $fileSource,
                'expectedResponseData' => [
                    'id' => $fileSource->getId(),
                    'user_id' => $fileSource->getUserId(),
                    'type' => Type::FILE->value,
                    'label' => $fileSource->getLabel(),
                ],
            ],
            Type::RUN->value => [
                'source' => $runSource,
                'expectedResponseData' => [
                    'id' => $runSource->getId(),
                    'user_id' => $userId,
                    'type' => Type::RUN->value,
                    'parent' => $runSource->getParent()?->getId(),
                    'parameters' => [],
                    'state' => State::REQUESTED->value,
                ],
            ],
            Type::RUN->value . ': preparation failed' => [
                'source' => $failedRunSource,
                'expectedResponseData' => [
                    'id' => $failedRunSource->getId(),
                    'user_id' => $userId,
                    'type' => Type::RUN->value,
                    'parent' => $failedRunSource->getParent()?->getId(),
                    'parameters' => [],
                    'state' => State::FAILED->value,
                    'failure_reason' => FailureReason::GIT_CLONE->value,
                    'failure_message' => $failureMessage,
                ],
            ],
        ];
    }

//    public function testCreateUnauthorizedUser(): void
//    {
//        $response = $this->client->makeCreateSourceRequest($this->invalidToken, []);
//
//        $this->responseAsserter->assertUnauthorizedResponse($response);
//    }
//
//    /**
//     * @dataProvider createInvalidRequestDataProvider
//     *
//     * @param array<string, string> $requestParameters
//     * @param array<string, string> $expectedResponseData
//     */
//    public function testCreateInvalidSourceRequest(array $requestParameters, array $expectedResponseData): void
//    {
//        $response = $this->client->makeCreateSourceRequest($this->validToken, $requestParameters);
//
//        $this->responseAsserter->assertInvalidRequestJsonResponse($response, $expectedResponseData);
//    }
//
//    /**
//     * @return array<mixed>
//     */
//    public function createInvalidRequestDataProvider(): array
//    {
//        return [
//            'invalid source type' => [
//                'requestParameters' => [
//                    SourceRequestInterface::PARAMETER_TYPE => 'invalid',
//                ],
//                'expectedResponseData' => [
//                    'error' => [
//                        'type' => 'invalid_request',
//                        'payload' => [
//                            'type' => [
//                                'value' => 'invalid',
//                                'message' => InvalidSourceTypeRequest::ERROR_MESSAGE,
//                            ],
//                        ],
//                    ],
//                ],
//            ],
//            'git source missing host url' => [
//                'requestParameters' => [
//                    SourceRequestInterface::PARAMETER_TYPE => Type::GIT->value,
//                ],
//                'expectedResponseData' => [
//                    'error' => [
//                        'type' => 'invalid_request',
//                        'payload' => [
//                            'host-url' => [
//                                'value' => '',
//                                'message' => 'This value should not be blank.',
//                            ],
//                        ],
//                    ],
//                ],
//            ],
//        ];
//    }
//
//    /**
//     * @dataProvider createSuccessDataProvider
//     *
//     * @param array<string, string> $requestParameters
//     * @param array<mixed>          $expected
//     */
//    public function testCreateSuccess(array $requestParameters, array $expected): void
//    {
//        $response = $this->client->makeCreateSourceRequest($this->validToken, $requestParameters);
//
//        $sources = $this->sourceRepository->findAll();
//        self::assertIsArray($sources);
//        self::assertCount(1, $sources);
//
//        $source = $sources[0];
//        self::assertInstanceOf(SourceInterface::class, $source);
//
//        $expected['id'] = $source->getId();
//        $expected['user_id'] = $this->authenticationConfiguration->authenticatedUserId;
//
//        $this->responseAsserter->assertSuccessfulJsonResponse($response, $expected);
//    }
//
//    /**
//     * @return array<mixed>
//     */
//    public function createSuccessDataProvider(): array
//    {
//        $hostUrl = 'https://example.com/repository.git';
//        $path = '/';
//        $credentials = md5((string) rand());
//        $label = 'file source label';
//
//        return [
//            'git source, credentials missing' => [
//                'requestParameters' => [
//                    SourceRequestInterface::PARAMETER_TYPE => Type::GIT->value,
//                    GitSourceRequest::PARAMETER_HOST_URL => $hostUrl,
//                    GitSourceRequest::PARAMETER_PATH => $path
//                ],
//                'expected' => [
//                    'user_id' => self::AUTHENTICATED_USER_ID_PLACEHOLDER,
//                    'type' => Type::GIT->value,
//                    'host_url' => $hostUrl,
//                    'path' => $path,
//                    'has_credentials' => false,
//                ],
//            ],
//            'git source, credentials present' => [
//                'requestParameters' => [
//                    SourceRequestInterface::PARAMETER_TYPE => Type::GIT->value,
//                    GitSourceRequest::PARAMETER_HOST_URL => $hostUrl,
//                    GitSourceRequest::PARAMETER_PATH => $path,
//                    GitSourceRequest::PARAMETER_CREDENTIALS => $credentials,
//                ],
//                'expected' => [
//                    'user_id' => self::AUTHENTICATED_USER_ID_PLACEHOLDER,
//                    'type' => Type::GIT->value,
//                    'host_url' => $hostUrl,
//                    'path' => $path,
//                    'has_credentials' => true,
//                ],
//            ],
//            'file source' => [
//                'requestParameters' => [
//                    SourceRequestInterface::PARAMETER_TYPE => Type::FILE->value,
//                    FileSourceRequest::PARAMETER_LABEL => $label
//                ],
//                'expected' => [
//                    'user_id' => self::AUTHENTICATED_USER_ID_PLACEHOLDER,
//                    'type' => Type::FILE->value,
//                    'label' => $label,
//                ],
//            ],
//        ];
//    }
}
