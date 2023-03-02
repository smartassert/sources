<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Enum\RunSource\State;
use App\Enum\Source\Type;
use App\Request\FileSourceRequest;
use App\Request\OriginSourceRequest;
use App\Services\DirectoryListingFilter;
use App\Tests\Application\AbstractPrepareSourceTest;
use League\Flysystem\FilesystemOperator;

class PrepareSourceTest extends AbstractPrepareSourceTest
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

    public function testPrepareFileSource(): void
    {
        $createResponse = $this->applicationClient->makeCreateSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            [
                OriginSourceRequest::PARAMETER_TYPE => Type::FILE->value,
                FileSourceRequest::PARAMETER_LABEL => 'file source label',
            ]
        );
        $createResponseData = json_decode($createResponse->getBody()->getContents(), true);
        \assert(is_array($createResponseData));

        $fileSourceId = $createResponseData['id'] ?? null;
        $fileSourceUserId = $createResponseData['user_id'] ?? null;

        $sourceIdentifier = 'Source/yaml_valid';

        $sourceFiles = $this->listingFilter->filter(
            $this->fixtureStorage->listContents($sourceIdentifier, true),
            $sourceIdentifier
        );

        foreach ($sourceFiles as $sourceFilePath) {
            $addFileResponse = $this->applicationClient->makeAddFileRequest(
                self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
                $fileSourceId,
                $sourceFilePath,
                trim($this->fixtureStorage->read($sourceIdentifier . '/' . $sourceFilePath))
            );

            $this->responseAsserter->assertSuccessfulResponseWithNoBody($addFileResponse);
        }

        $prepareResponse = $this->applicationClient->makePrepareSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $fileSourceId,
            []
        );

        $this->responseAsserter->assertPrepareSourceSuccessResponseWithUnknownData($prepareResponse);

        $responseData = json_decode($prepareResponse->getBody()->getContents(), true);
        self::assertIsArray($responseData);
        self::assertArrayHasKey('id', $responseData);

        $runSourceId = $responseData['id'];

        $this->responseAsserter->assertPrepareSourceSuccessResponse($prepareResponse, [
            'id' => $runSourceId,
            'user_id' => $fileSourceUserId,
            'type' => Type::RUN->value,
            'parent' => $fileSourceId,
            'parameters' => [],
            'state' => State::REQUESTED->value,
        ]);

        $this->waitUntilSourceIsPrepared($runSourceId);

        $readResponse = $this->applicationClient->makeReadSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $runSourceId
        );

        $expectedReadResponseBody = trim($this->fixtureStorage->read('RunSource/source_yaml_entire.yaml'));

        $this->responseAsserter->assertReadSourceSuccessResponse($readResponse, $expectedReadResponseBody);
    }

    private function waitUntilSourceIsPrepared(string $runSourceId): void
    {
        $timeout = 30000;
        $duration = 0;
        $period = 1000;
        $state = null;

        while (State::PREPARED->value !== $state) {
            $getResponse = $this->applicationClient->makeGetSourceRequest(
                self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
                $runSourceId
            );

            if (200 === $getResponse->getStatusCode()) {
                $responseData = json_decode($getResponse->getBody()->getContents(), true);

                if (is_array($responseData)) {
                    $state = $responseData['state'] ?? null;
                }

                if (State::PREPARED->value !== $state) {
                    $duration += $period;

                    if ($duration >= $timeout) {
                        throw new \RuntimeException('Timed out waiting for "' . $runSourceId . '" to prepare');
                    }

                    usleep($period);
                }
            }
        }
    }
}
