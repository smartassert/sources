<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Enum\SerializedSuite\State;
use App\Enum\Source\Type;
use App\Request\FileSourceRequest;
use App\Request\OriginSourceRequest;
use App\Request\SuiteRequest;
use App\Services\DirectoryListingFilter;
use App\Tests\Application\AbstractCreateSerializedSuiteTest;
use League\Flysystem\FilesystemOperator;

class CreateSerializedSuiteTest extends AbstractCreateSerializedSuiteTest
{
    use GetClientAdapterTrait;

    private FilesystemOperator $fixtureStorage;
    private DirectoryListingFilter $listingFilter;

    protected function setUp(): void
    {
        parent::setUp();

        $fixtureStorage = self::getContainer()->get('test_fixtures.storage');
        \assert($fixtureStorage instanceof FilesystemOperator);
        $this->fixtureStorage = $fixtureStorage;

        $listingFilter = self::getContainer()->get(DirectoryListingFilter::class);
        \assert($listingFilter instanceof DirectoryListingFilter);
        $this->listingFilter = $listingFilter;
    }

    public function testSerializeSuite(): void
    {
        $createSourceResponse = $this->applicationClient->makeCreateSourceRequest(
            self::$authenticationConfiguration->getApiToken(self::USER_1_EMAIL),
            [
                OriginSourceRequest::PARAMETER_TYPE => Type::FILE->value,
                FileSourceRequest::PARAMETER_LABEL => 'file source label',
            ]
        );

        $createSourceResponseData = json_decode($createSourceResponse->getBody()->getContents(), true);
        \assert(is_array($createSourceResponseData));
        $fileSourceId = $createSourceResponseData['id'] ?? null;

        $sourceIdentifier = 'Source/yaml_valid';

        $sourceFiles = $this->listingFilter->filter(
            $this->fixtureStorage->listContents($sourceIdentifier, true),
            $sourceIdentifier
        );

        foreach ($sourceFiles as $sourceFilePath) {
            $addFileResponse = $this->applicationClient->makeAddFileRequest(
                self::$authenticationConfiguration->getApiToken(self::USER_1_EMAIL),
                $fileSourceId,
                $sourceFilePath,
                trim($this->fixtureStorage->read($sourceIdentifier . '/' . $sourceFilePath))
            );

            $this->responseAsserter->assertSuccessfulResponseWithNoBody($addFileResponse);
        }

        $createSuiteResponse = $this->applicationClient->makeCreateSuiteRequest(
            self::$authenticationConfiguration->getApiToken(self::USER_1_EMAIL),
            [
                SuiteRequest::PARAMETER_SOURCE_ID => $fileSourceId,
                SuiteRequest::PARAMETER_LABEL => md5((string) rand()),
                SuiteRequest::PARAMETER_TESTS => [
                    'test1.yaml',
                    'test2.yaml',
                ],
            ]
        );

        $createSuiteResponseData = json_decode($createSuiteResponse->getBody()->getContents(), true);
        \assert(is_array($createSuiteResponseData));
        $suiteId = $createSuiteResponseData['id'] ?? null;

        $createSerializedSuiteResponse = $this->applicationClient->makeCreateSerializedSuiteRequest(
            self::$authenticationConfiguration->getApiToken(self::USER_1_EMAIL),
            $suiteId,
            []
        );

        $createSerializedSuiteResponseData = json_decode(
            $createSerializedSuiteResponse->getBody()->getContents(),
            true
        );
        \assert(is_array($createSerializedSuiteResponseData));
        $serializedSuiteId = $createSerializedSuiteResponseData['id'] ?? null;

        $this->waitUntilSuiteIsSerialized($serializedSuiteId);

        $readResponse = $this->applicationClient->makeReadSerializedSuiteRequest(
            self::$authenticationConfiguration->getApiToken(self::USER_1_EMAIL),
            $serializedSuiteId
        );

        $expectedReadResponseBody = trim($this->fixtureStorage->read('SerializedSuite/suite_yaml_entire.yaml'));

        $this->responseAsserter->assertReadSerializedSuiteSuccessResponse($readResponse, $expectedReadResponseBody);
    }

    private function waitUntilSuiteIsSerialized(string $serializedSuiteId): void
    {
        $timeout = 30000;
        $duration = 0;
        $period = 1000;
        $state = null;

        while (State::PREPARED->value !== $state) {
            $getResponse = $this->applicationClient->makeGetSerializedSuiteRequest(
                self::$authenticationConfiguration->getApiToken(self::USER_1_EMAIL),
                $serializedSuiteId
            );

            if (200 === $getResponse->getStatusCode()) {
                $responseData = json_decode($getResponse->getBody()->getContents(), true);

                if (is_array($responseData)) {
                    $state = $responseData['state'] ?? null;
                }

                if (State::PREPARED->value !== $state) {
                    $duration += $period;

                    if ($duration >= $timeout) {
                        throw new \RuntimeException('Timed out waiting for "' . $serializedSuiteId . '" to prepare');
                    }

                    usleep($period);
                }
            }
        }
    }
}
