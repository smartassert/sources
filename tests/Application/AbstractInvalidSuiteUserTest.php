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

    private Suite $accessibleSourceInaccessibleSuite;

    private Suite $inaccessibleSourceInaccessibleSuite;

    protected function setUp(): void
    {
        parent::setUp();

        $repository = self::getContainer()->get(SuiteRepository::class);
        \assert($repository instanceof SuiteRepository);

        $inaccessibleSource = SourceOriginFactory::create(type: 'file', label: 'inaccessible source');
        \assert($inaccessibleSource instanceof FileSource);

        $accessibleSource = SourceOriginFactory::create(
            type: 'file',
            userId: self::$authenticationConfiguration->getUser(self::USER_1_EMAIL)->id,
            label: 'accessible source',
        );
        \assert($accessibleSource instanceof FileSource);

        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);
        $sourceRepository->save($inaccessibleSource);
        $sourceRepository->save($accessibleSource);

        $this->accessibleSourceInaccessibleSuite = SuiteFactory::create(source: $accessibleSource);
        $this->inaccessibleSourceInaccessibleSuite = SuiteFactory::create(source: $inaccessibleSource);
        $repository->save($this->inaccessibleSourceInaccessibleSuite);
    }

    public function testGetSuiteValidSourceUserInvalidSuiteUser(): void
    {
        $response = $this->applicationClient->makeGetSuiteRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $this->accessibleSourceInaccessibleSuite->id,
        );

        $this->responseAsserter->assertNotFoundResponse($response);
    }

    public function testGetSuiteInvalidSourceUserInvalidSuiteUser(): void
    {
        $response = $this->applicationClient->makeGetSuiteRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $this->inaccessibleSourceInaccessibleSuite->id,
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testUpdateSuiteValidSourceUserInvalidSuiteUser(): void
    {
        $response = $this->applicationClient->makeUpdateSuiteRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $this->accessibleSourceInaccessibleSuite->id,
            [
                SuiteRequest::PARAMETER_SOURCE_ID => $this->accessibleSourceInaccessibleSuite->id,
                SuiteRequest::PARAMETER_LABEL => md5((string) rand()),
                SuiteRequest::PARAMETER_TESTS => [],
            ]
        );

        $this->responseAsserter->assertNotFoundResponse($response);
    }

    public function testUpdateSuiteInvalidSourceUserInvalidSuiteUser(): void
    {
        $response = $this->applicationClient->makeUpdateSuiteRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $this->inaccessibleSourceInaccessibleSuite->id,
            [
                SuiteRequest::PARAMETER_SOURCE_ID => $this->inaccessibleSourceInaccessibleSuite->id,
                SuiteRequest::PARAMETER_LABEL => md5((string) rand()),
                SuiteRequest::PARAMETER_TESTS => [],
            ]
        );

        $this->responseAsserter->assertNotFoundResponse($response);
    }
}
