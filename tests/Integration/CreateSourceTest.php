<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\SourceInterface;
use App\Enum\Source\Type;
use App\Repository\SourceRepository;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use App\Request\InvalidSourceTypeRequest;
use App\Request\SourceRequestInterface;
use App\Tests\Services\EntityRemover;

class CreateSourceTest extends AbstractIntegrationTest
{
    private SourceRepository $sourceRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);
        $this->sourceRepository = $sourceRepository;

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
        }
    }

    public function testCreateUnauthorizedUser(): void
    {
        $response = $this->client->makeCreateSourceRequest($this->invalidToken, []);

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    /**
     * @dataProvider createInvalidRequestDataProvider
     *
     * @param array<string, string> $requestParameters
     * @param array<string, string> $expectedResponseData
     */
    public function testCreateInvalidSourceRequest(array $requestParameters, array $expectedResponseData): void
    {
        $response = $this->client->makeCreateSourceRequest($this->validToken, $requestParameters);

        $this->responseAsserter->assertInvalidRequestJsonResponse($response, $expectedResponseData);
    }

    /**
     * @return array<mixed>
     */
    public function createInvalidRequestDataProvider(): array
    {
        return [
            'invalid source type' => [
                'requestParameters' => [
                    SourceRequestInterface::PARAMETER_TYPE => 'invalid',
                ],
                'expectedResponseData' => [
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            'type' => [
                                'value' => 'invalid',
                                'message' => InvalidSourceTypeRequest::ERROR_MESSAGE,
                            ],
                        ],
                    ],
                ],
            ],
            'git source missing host url' => [
                'requestParameters' => [
                    SourceRequestInterface::PARAMETER_TYPE => Type::GIT->value,
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
     * @dataProvider createSuccessDataProvider
     *
     * @param array<string, string> $requestParameters
     * @param array<mixed>          $expected
     */
    public function testCreateSuccess(array $requestParameters, array $expected): void
    {
        $response = $this->client->makeCreateSourceRequest($this->validToken, $requestParameters);

        $sources = $this->sourceRepository->findAll();
        self::assertIsArray($sources);
        self::assertCount(1, $sources);

        $source = $sources[0];
        self::assertInstanceOf(SourceInterface::class, $source);

        $expected['id'] = $source->getId();
        $expected['user_id'] = $this->authenticationConfiguration->authenticatedUserId;

        $this->responseAsserter->assertSuccessfulJsonResponse($response, $expected);
    }

    /**
     * @return array<mixed>
     */
    public function createSuccessDataProvider(): array
    {
        $hostUrl = 'https://example.com/repository.git';
        $path = '/';
        $credentials = md5((string) rand());
        $label = 'file source label';

        return [
            'git source, credentials missing' => [
                'requestParameters' => [
                    SourceRequestInterface::PARAMETER_TYPE => Type::GIT->value,
                    GitSourceRequest::PARAMETER_HOST_URL => $hostUrl,
                    GitSourceRequest::PARAMETER_PATH => $path
                ],
                'expected' => [
                    'user_id' => self::AUTHENTICATED_USER_ID_PLACEHOLDER,
                    'type' => Type::GIT->value,
                    'host_url' => $hostUrl,
                    'path' => $path,
                    'has_credentials' => false,
                ],
            ],
            'git source, credentials present' => [
                'requestParameters' => [
                    SourceRequestInterface::PARAMETER_TYPE => Type::GIT->value,
                    GitSourceRequest::PARAMETER_HOST_URL => $hostUrl,
                    GitSourceRequest::PARAMETER_PATH => $path,
                    GitSourceRequest::PARAMETER_CREDENTIALS => $credentials,
                ],
                'expected' => [
                    'user_id' => self::AUTHENTICATED_USER_ID_PLACEHOLDER,
                    'type' => Type::GIT->value,
                    'host_url' => $hostUrl,
                    'path' => $path,
                    'has_credentials' => true,
                ],
            ],
            'file source' => [
                'requestParameters' => [
                    SourceRequestInterface::PARAMETER_TYPE => Type::FILE->value,
                    FileSourceRequest::PARAMETER_LABEL => $label
                ],
                'expected' => [
                    'user_id' => self::AUTHENTICATED_USER_ID_PLACEHOLDER,
                    'type' => Type::FILE->value,
                    'label' => $label,
                ],
            ],
        ];
    }
}
