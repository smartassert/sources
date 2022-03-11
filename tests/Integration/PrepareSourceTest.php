<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Enum\RunSource\State;
use App\Enum\Source\Type;
use App\Services\DirectoryListingFilter;
use App\Tests\Application\AbstractPrepareSourceTest;
use App\Tests\Services\SourceProvider;
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
        $this->sourceProvider->initialize([SourceProvider::FILE_WITHOUT_RUN_SOURCE]);
        $fileSource = $this->sourceProvider->get(SourceProvider::FILE_WITHOUT_RUN_SOURCE);

        $sourceIdentifier = 'Source/yaml_valid';

        $sourceFiles = $this->listingFilter->filter(
            $this->fixtureStorage->listContents($sourceIdentifier, true),
            $sourceIdentifier
        );

        foreach ($sourceFiles as $sourceFilePath) {
            $addFileResponse = $this->applicationClient->makeAddFileRequest(
                $this->authenticationConfiguration->validToken,
                $fileSource->getId(),
                $sourceFilePath,
                trim($this->fixtureStorage->read($sourceIdentifier . '/' . $sourceFilePath))
            );

            $this->responseAsserter->assertSuccessfulResponseWithNoBody($addFileResponse);
        }

        $prepareResponse = $this->applicationClient->makePrepareSourceRequest(
            $this->authenticationConfiguration->validToken,
            $fileSource->getId(),
            []
        );

        $this->responseAsserter->assertPrepareSourceSuccessResponseWithUnknownData($prepareResponse);

        $responseData = json_decode($prepareResponse->getBody()->getContents(), true);
        self::assertIsArray($responseData);
        self::assertArrayHasKey('id', $responseData);

        $runSourceId = $responseData['id'];

        $this->responseAsserter->assertPrepareSourceSuccessResponse($prepareResponse, [
            'id' => $runSourceId,
            'user_id' => $fileSource->getUserId(),
            'type' => Type::RUN->value,
            'parent' => $fileSource->getId(),
            'parameters' => [],
            'state' => State::REQUESTED->value,
        ]);

        $this->waitUntilSourceIsPrepared($runSourceId);

        $readResponse = $this->applicationClient->makeReadSourceRequest(
            $this->authenticationConfiguration->validToken,
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
                $this->authenticationConfiguration->validToken,
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
