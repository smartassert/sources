<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\FileSource;
use App\Entity\Suite;
use App\Repository\FileSourceRepository;
use App\Repository\SourceRepository;
use App\Repository\SuiteRepository;
use App\Request\FileSourceRequest;
use App\Services\EntityIdFactory;
use App\Tests\Model\UserId;

abstract class AbstractInvalidSuiteUserTest extends AbstractApplicationTest
{
    public const FILENAME = 'filename.yaml';

    private FileSource $inaccessibleSource;
    private FileSource $accessibleSource;
    private Suite $inaccessibleSuite;
    private Suite $accessibleSuite;

    protected function setUp(): void
    {
        parent::setUp();

        $repository = self::getContainer()->get(SuiteRepository::class);
        \assert($repository instanceof SuiteRepository);

        $idFactory = new EntityIdFactory();

        $this->inaccessibleSource = new FileSource($idFactory->create(), UserId::create(), 'inaccessible source');
        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);
        $sourceRepository->save($this->inaccessibleSource);

        $createSourceResponse = $this->applicationClient->makeCreateFileSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(),
            [
                FileSourceRequest::PARAMETER_LABEL => 'label',
            ]
        );
        $createSourceResponseData = json_decode($createSourceResponse->getBody()->getContents(), true);
        \assert(is_array($createSourceResponseData));
        $accessibleSourceId = $createSourceResponseData['id'] ?? null;
        \assert(is_string($accessibleSourceId));

        $fileSourceRepository = self::getContainer()->get(FileSourceRepository::class);
        \assert($fileSourceRepository instanceof FileSourceRepository);

        $accessibleSource = $fileSourceRepository->find($accessibleSourceId);
        \assert($accessibleSource instanceof FileSource);
        $this->accessibleSource = $accessibleSource;

        $this->inaccessibleSuite = new Suite(
            $idFactory->create(),
            $this->inaccessibleSource,
            'inaccessible suite',
            ['test.yaml']
        );
        $repository->save($this->inaccessibleSuite);

        $this->accessibleSuite = new Suite(
            $idFactory->create(),
            $this->accessibleSource,
            'accessible suite',
            ['test.yaml']
        );
        $repository->save($this->accessibleSuite);
    }

    public function testGetSuiteInvalidSourceUserValidSuiteUser(): void
    {
        $response = $this->applicationClient->makeGetSuiteRequest(
            self::$authenticationConfiguration->getValidApiToken(),
            $this->inaccessibleSource->getId(),
            $this->accessibleSuite->id,
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testGetSuiteValidSourceUserInvalidSuiteUser(): void
    {
        $response = $this->applicationClient->makeGetSuiteRequest(
            self::$authenticationConfiguration->getValidApiToken(),
            $this->accessibleSource->getId(),
            $this->inaccessibleSuite->id,
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testGetSuiteInvalidSourceUserInvalidSuiteUser(): void
    {
        $response = $this->applicationClient->makeGetSuiteRequest(
            self::$authenticationConfiguration->getValidApiToken(),
            $this->inaccessibleSource->getId(),
            $this->inaccessibleSuite->id,
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }
}
