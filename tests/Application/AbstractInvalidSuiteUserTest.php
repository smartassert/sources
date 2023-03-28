<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\FileSource;
use App\Entity\SerializedSuite;
use App\Entity\Suite;
use App\Repository\SerializedSuiteRepository;
use App\Repository\SourceRepository;
use App\Repository\SuiteRepository;
use App\Request\SuiteRequest;
use App\Services\EntityIdFactory;
use App\Tests\Services\SourceOriginFactory;
use App\Tests\Services\SuiteFactory;

abstract class AbstractInvalidSuiteUserTest extends AbstractApplicationTest
{
    public const FILENAME = 'filename.yaml';

    private Suite $suite;
    private SerializedSuite $serializedSuite;

    protected function setUp(): void
    {
        parent::setUp();

        $inaccessibleSource = SourceOriginFactory::create(type: 'file', label: 'inaccessible source');
        \assert($inaccessibleSource instanceof FileSource);

        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);
        $sourceRepository->save($inaccessibleSource);

        $this->suite = SuiteFactory::create(source: $inaccessibleSource);
        $suiteRepository = self::getContainer()->get(SuiteRepository::class);
        \assert($suiteRepository instanceof SuiteRepository);
        $suiteRepository->save($this->suite);

        $this->serializedSuite = new SerializedSuite(
            (new EntityIdFactory())->create(),
            $this->suite,
            []
        );

        $serializedSuiteRepository = self::getContainer()->get(SerializedSuiteRepository::class);
        \assert($serializedSuiteRepository instanceof SerializedSuiteRepository);
        $serializedSuiteRepository->save($this->serializedSuite);
    }

    public function testGetSuite(): void
    {
        $response = $this->applicationClient->makeGetSuiteRequest(
            self::$authenticationConfiguration->getApiToken(self::USER_1_EMAIL),
            $this->suite->id,
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testUpdateSuite(): void
    {
        $response = $this->applicationClient->makeUpdateSuiteRequest(
            self::$authenticationConfiguration->getApiToken(self::USER_1_EMAIL),
            $this->suite->id,
            [
                SuiteRequest::PARAMETER_SOURCE_ID => $this->suite->id,
                SuiteRequest::PARAMETER_LABEL => md5((string) rand()),
                SuiteRequest::PARAMETER_TESTS => [],
            ]
        );

        $this->responseAsserter->assertNotFoundResponse($response);
    }

    public function testDeleteSuite(): void
    {
        $response = $this->applicationClient->makeDeleteSuiteRequest(
            self::$authenticationConfiguration->getApiToken(self::USER_1_EMAIL),
            $this->suite->id,
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testCreateSerializedSuite(): void
    {
        $response = $this->applicationClient->makeCreateSerializedSuiteRequest(
            self::$authenticationConfiguration->getApiToken(self::USER_1_EMAIL),
            $this->suite->id,
            [],
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testReadSerializedSuite(): void
    {
        $response = $this->applicationClient->makeReadSerializedSuiteRequest(
            self::$authenticationConfiguration->getApiToken(self::USER_1_EMAIL),
            $this->serializedSuite->id,
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testGetSerializedSuite(): void
    {
        $response = $this->applicationClient->makeGetSerializedSuiteRequest(
            self::$authenticationConfiguration->getApiToken(self::USER_1_EMAIL),
            $this->serializedSuite->id,
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }
}
