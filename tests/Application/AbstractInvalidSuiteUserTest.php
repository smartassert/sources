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
use App\Tests\Services\StringFactory;
use App\Tests\Services\SuiteFactory;
use Symfony\Component\Uid\Ulid;

abstract class AbstractInvalidSuiteUserTest extends AbstractApplicationTest
{
    public const FILENAME = 'filename.yaml';

    private Suite $suite;
    private SerializedSuite $serializedSuite;

    #[\Override]
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
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->suite->id,
        );

        self::assertSame(403, $response->getStatusCode());
    }

    public function testUpdateSuite(): void
    {
        $response = $this->applicationClient->makeUpdateSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->suite->id,
            [
                SuiteRequest::PARAMETER_SOURCE_ID => $this->suite->id,
                SuiteRequest::PARAMETER_LABEL => StringFactory::createRandom(),
                SuiteRequest::PARAMETER_TESTS => [],
            ]
        );

        self::assertSame(403, $response->getStatusCode());
    }

    public function testDeleteSuite(): void
    {
        $response = $this->applicationClient->makeDeleteSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->suite->id,
        );

        self::assertSame(403, $response->getStatusCode());
    }

    public function testCreateSerializedSuite(): void
    {
        $response = $this->applicationClient->makeCreateSerializedSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            (string) new Ulid(),
            $this->suite->id,
            [],
        );

        self::assertSame(403, $response->getStatusCode());
    }

    public function testReadSerializedSuite(): void
    {
        $response = $this->applicationClient->makeReadSerializedSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->serializedSuite->id,
        );

        self::assertSame(403, $response->getStatusCode());
    }

    public function testGetSerializedSuite(): void
    {
        $response = $this->applicationClient->makeGetSerializedSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->serializedSuite->id,
        );

        self::assertSame(403, $response->getStatusCode());
    }
}
