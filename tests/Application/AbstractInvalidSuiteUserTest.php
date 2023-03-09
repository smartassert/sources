<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\FileSource;
use App\Entity\Suite;
use App\Repository\SourceRepository;
use App\Repository\SuiteRepository;
use App\Request\SuiteRequest;
use App\Tests\Services\SourceOriginFactory;
use App\Tests\Services\SuiteFactory;

abstract class AbstractInvalidSuiteUserTest extends AbstractApplicationTest
{
    public const FILENAME = 'filename.yaml';

    private Suite $suite;

    protected function setUp(): void
    {
        parent::setUp();

        $repository = self::getContainer()->get(SuiteRepository::class);
        \assert($repository instanceof SuiteRepository);

        $inaccessibleSource = SourceOriginFactory::create(type: 'file', label: 'inaccessible source');
        \assert($inaccessibleSource instanceof FileSource);

        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);
        $sourceRepository->save($inaccessibleSource);

        $this->suite = SuiteFactory::create(source: $inaccessibleSource);
        $repository->save($this->suite);
    }

    public function testGetSuite(): void
    {
        $response = $this->applicationClient->makeGetSuiteRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $this->suite->id,
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testUpdateSuite(): void
    {
        $response = $this->applicationClient->makeUpdateSuiteRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
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
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $this->suite->id,
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testSerializeSuiteValidSourceUserInvalidSuiteUser(): void
    {
        $response = $this->applicationClient->makeSerializeSuiteRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $this->accessibleSourceInaccessibleSuite->id,
            [],
        );

        $this->responseAsserter->assertNotFoundResponse($response);
    }

    public function testSerializeSuiteInvalidSourceUserInvalidSuiteUser(): void
    {
        $response = $this->applicationClient->makeSerializeSuiteRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $this->inaccessibleSourceInaccessibleSuite->id,
            [],
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }
}
