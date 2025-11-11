<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Enum\SerializedSuite\State;
use App\Request\FileSourceRequest;
use App\Request\SuiteRequest;
use App\Services\DirectoryListingFilter;
use App\Tests\Application\AbstractCreateSerializedSuiteTest;
use App\Tests\Services\StringFactory;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Uid\Ulid;

class CreateSerializedSuiteTest extends AbstractCreateSerializedSuiteTest
{
    use GetClientAdapterTrait;
    use WaitUntilSerializedSuiteStateTrait;

    private FilesystemOperator $fixtureStorage;
    private DirectoryListingFilter $listingFilter;

    #[\Override]
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
        $createSourceResponse = $this->applicationClient->makeCreateFileSourceRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            [
                FileSourceRequest::PARAMETER_LABEL => 'file source label',
            ]
        );

        $createSourceResponseData = json_decode($createSourceResponse->getBody()->getContents(), true);
        \assert(is_array($createSourceResponseData));
        $fileSourceId = $createSourceResponseData['id'] ?? null;
        \assert(is_string($fileSourceId));

        $sourceIdentifier = 'Source/yaml_valid';

        $sourceFiles = $this->listingFilter->filter(
            $this->fixtureStorage->listContents($sourceIdentifier, true),
            $sourceIdentifier
        );

        foreach ($sourceFiles as $sourceFilePath) {
            $addFileResponse = $this->applicationClient->makeAddFileRequest(
                self::$apiTokens->get(self::USER_1_EMAIL),
                $fileSourceId,
                $sourceFilePath,
                trim($this->fixtureStorage->read($sourceIdentifier . '/' . $sourceFilePath))
            );

            self::assertSame(200, $addFileResponse->getStatusCode());
            self::assertSame('', $addFileResponse->getHeaderLine('content-type'));
            self::assertSame('', $addFileResponse->getBody()->getContents());
        }

        $createSuiteResponse = $this->applicationClient->makeCreateSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            [
                SuiteRequest::PARAMETER_SOURCE_ID => $fileSourceId,
                SuiteRequest::PARAMETER_LABEL => StringFactory::createRandom(),
                SuiteRequest::PARAMETER_TESTS => [
                    'test1.yaml',
                    'test2.yaml',
                ],
            ]
        );

        $createSuiteResponseData = json_decode($createSuiteResponse->getBody()->getContents(), true);
        \assert(is_array($createSuiteResponseData));
        $suiteId = $createSuiteResponseData['id'] ?? null;
        \assert(is_string($suiteId));

        $serializedSuiteId = (string) (new Ulid());

        $this->applicationClient->makeCreateSerializedSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $serializedSuiteId,
            $suiteId,
            []
        );

        $this->waitUntilSuiteStateIs($serializedSuiteId, State::PREPARED);

        $readResponse = $this->applicationClient->makeReadSerializedSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $serializedSuiteId
        );

        $expectedReadResponseBody = trim($this->fixtureStorage->read('SerializedSuite/suite_yaml_entire.yaml'));

        self::assertSame(200, $readResponse->getStatusCode());
        self::assertSame('text/x-yaml; charset=utf-8', $readResponse->getHeaderLine('content-type'));
        self::assertSame(
            $expectedReadResponseBody,
            $readResponse->getBody()->getContents()
        );
    }
}
