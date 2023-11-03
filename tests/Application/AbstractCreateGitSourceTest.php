<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\SourceInterface;
use App\Enum\Source\Type;
use App\Repository\SourceRepository;
use App\Request\GitSourceRequest;
use App\Tests\DataProvider\CreateUpdateGitSourceDataProviderTrait;
use App\Tests\Services\SourceRequestTypeMatcher;

abstract class AbstractCreateGitSourceTest extends AbstractApplicationTest
{
    use CreateUpdateGitSourceDataProviderTrait;

    /**
     * @dataProvider createUpdateGitSourceInvalidRequestDataProvider
     *
     * @param array<string, string> $requestParameters
     * @param array<string, string> $expectedResponseData
     */
    public function testCreateInvalidSourceRequest(array $requestParameters, array $expectedResponseData): void
    {
        $response = $this->applicationClient->makeCreateGitSourceRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $requestParameters
        );

        $this->responseAsserter->assertInvalidRequestJsonResponse($response, $expectedResponseData);
    }

    /**
     * @dataProvider createGitSourceSuccessDataProvider
     *
     * @param array<string, string> $requestParameters
     * @param array<mixed>          $expected
     */
    public function testCreateSuccess(array $requestParameters, array $expected): void
    {
        $response = $this->applicationClient->makeCreateGitSourceRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $requestParameters
        );

        $sources = [];
        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        if ($sourceRepository instanceof SourceRepository) {
            $sources = $sourceRepository->findAll();
        }

        self::assertIsArray($sources);
        self::assertCount(1, $sources);

        $source = $sources[0];
        self::assertInstanceOf(SourceInterface::class, $source);

        $expected['id'] = $source->getId();
        $expected['user_id'] = self::$users->get(self::USER_1_EMAIL)->id;

        $this->responseAsserter->assertSuccessfulJsonResponse($response, $expected);
    }

    /**
     * @return array<mixed>
     */
    public static function createGitSourceSuccessDataProvider(): array
    {
        $label = md5((string) rand());
        $hostUrl = 'https://example.com/' . md5((string) rand()) . '.git';
        $path = '/' . md5((string) rand());
        $credentials = md5((string) rand());

        return [
            'git source, credentials missing' => [
                'requestParameters' => [
                    GitSourceRequest::PARAMETER_LABEL => $label,
                    GitSourceRequest::PARAMETER_HOST_URL => $hostUrl,
                    GitSourceRequest::PARAMETER_PATH => $path
                ],
                'expected' => [
                    'type' => Type::GIT->value,
                    'label' => $label,
                    'host_url' => $hostUrl,
                    'path' => $path,
                    'has_credentials' => false,
                ],
            ],
            'git source, credentials present' => [
                'requestParameters' => [
                    GitSourceRequest::PARAMETER_LABEL => $label,
                    GitSourceRequest::PARAMETER_HOST_URL => $hostUrl,
                    GitSourceRequest::PARAMETER_PATH => $path,
                    GitSourceRequest::PARAMETER_CREDENTIALS => $credentials,
                ],
                'expected' => [
                    'type' => Type::GIT->value,
                    'label' => $label,
                    'host_url' => $hostUrl,
                    'path' => $path,
                    'has_credentials' => true,
                ],
            ],
        ];
    }

    /**
     * @dataProvider createGitSourceSuccessDataProvider
     *
     * @param array<string, string> $requestParameters
     */
    public function testCreateIsIdempotent(array $requestParameters): void
    {
        $firstResponse = $this->applicationClient->makeCreateGitSourceRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $requestParameters
        );

        self::assertSame(200, $firstResponse->getStatusCode());

        $secondResponse = $this->applicationClient->makeCreateGitSourceRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $requestParameters
        );

        self::assertSame(200, $secondResponse->getStatusCode());
        self::assertSame($firstResponse->getBody()->getContents(), $secondResponse->getBody()->getContents());
    }

    /**
     * @dataProvider createGitSourceSuccessDataProvider
     *
     * @param array<string, string> $requestParameters
     */
    public function testCreateWithLabelOfDeletedSource(array $requestParameters): void
    {
        $firstCreateResponse = $this->applicationClient->makeCreateGitSourceRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $requestParameters
        );

        self::assertSame(200, $firstCreateResponse->getStatusCode());

        $firstCreateResponseData = json_decode($firstCreateResponse->getBody()->getContents(), true);
        \assert(is_array($firstCreateResponseData));
        $sourceId = $firstCreateResponseData['id'] ?? null;
        \assert(is_string($sourceId));

        $deleteResponse = $this->applicationClient->makeDeleteSourceRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $sourceId
        );

        self::assertSame(200, $deleteResponse->getStatusCode());

        $secondCreateResponse = $this->applicationClient->makeCreateGitSourceRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $requestParameters
        );

        self::assertSame(200, $secondCreateResponse->getStatusCode());

        $secondCreateResponseData = json_decode($secondCreateResponse->getBody()->getContents(), true);
        \assert(is_array($secondCreateResponseData));
        self::assertNotSame($sourceId, $secondCreateResponseData['id']);
    }

    /**
     * @dataProvider createSourceWithNonUniqueLabelDataProvider
     *
     * @param array<string, string> $targetCreateParameters
     * @param array<string, string> $conflictCreateParameters
     */
    public function testCreateSourceWithNonUniqueLabel(
        string $label,
        array $targetCreateParameters,
        array $conflictCreateParameters,
    ): void {
        if (SourceRequestTypeMatcher::matchesGitSourceRequest($targetCreateParameters)) {
            $firstRequestResponse = $this->applicationClient->makeCreateGitSourceRequest(
                self::$apiTokens->get(self::USER_1_EMAIL),
                $targetCreateParameters
            );
        } else {
            $firstRequestResponse = $this->applicationClient->makeCreateFileSourceRequest(
                self::$apiTokens->get(self::USER_1_EMAIL),
                $targetCreateParameters
            );
        }

        self::assertSame(200, $firstRequestResponse->getStatusCode());

        $secondRequestResponse = $this->applicationClient->makeCreateGitSourceRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $conflictCreateParameters
        );

        $this->responseAsserter->assertInvalidRequestJsonResponse(
            $secondRequestResponse,
            [
                'error' => [
                    'type' => 'invalid_request',
                    'payload' => [
                        'name' => 'label',
                        'value' => $label,
                        'message' => 'This label is being used by another source belonging to this user',
                    ],
                ],
            ]
        );
    }

    /**
     * @return array<mixed>
     */
    public static function createSourceWithNonUniqueLabelDataProvider(): array
    {
        $label = md5((string) rand());

        return [
            'git source with label of file source' => [
                'label' => $label,
                'targetCreateParameters' => [
                    GitSourceRequest::PARAMETER_LABEL => $label,
                ],
                'conflictCreateParameters' => [
                    GitSourceRequest::PARAMETER_LABEL => $label,
                    GitSourceRequest::PARAMETER_HOST_URL => md5((string) rand()),
                    GitSourceRequest::PARAMETER_PATH => md5((string) rand()),
                ],
            ],
            'git source with label of git source' => [
                'label' => $label,
                'targetCreateParameters' => [
                    GitSourceRequest::PARAMETER_LABEL => $label,
                    GitSourceRequest::PARAMETER_HOST_URL => md5((string) rand()),
                    GitSourceRequest::PARAMETER_PATH => md5((string) rand()),
                ],
                'conflictCreateParameters' => [
                    GitSourceRequest::PARAMETER_LABEL => $label,
                    GitSourceRequest::PARAMETER_HOST_URL => md5((string) rand()),
                    GitSourceRequest::PARAMETER_PATH => md5((string) rand()),
                ],
            ],
        ];
    }
}
