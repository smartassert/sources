<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\FileSource;
use App\Entity\Suite;
use App\Enum\Source\Type;
use App\Repository\FileSourceRepository;
use App\Repository\SourceRepository;
use App\Repository\SuiteRepository;
use App\Request\FileSourceRequest;
use App\Request\OriginSourceRequest;
use App\Tests\Services\SourceOriginFactory;
use App\Tests\Services\SuiteFactory;

abstract class AbstractInvalidSuiteUserTest extends AbstractApplicationTest
{
    public const FILENAME = 'filename.yaml';

    private Suite $inaccessibleSuite;

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

        $createSourceResponse = $this->applicationClient->makeCreateSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            [
                OriginSourceRequest::PARAMETER_TYPE => Type::FILE->value,
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

        $this->inaccessibleSuite = SuiteFactory::create(source: $inaccessibleSource);
        $repository->save($this->inaccessibleSuite);
    }

    public function testGetSuiteInvalidSuiteUser(): void
    {
        $response = $this->applicationClient->makeGetSuiteRequest(
            self::$authenticationConfiguration->getValidApiToken(self::USER_1_EMAIL),
            $this->inaccessibleSuite->id,
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }
}
