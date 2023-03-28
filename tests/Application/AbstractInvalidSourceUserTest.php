<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Enum\Source\Type;
use App\Repository\SourceRepository;
use App\Request\OriginSourceRequest;
use App\Tests\Services\SourceOriginFactory;

abstract class AbstractInvalidSourceUserTest extends AbstractApplicationTest
{
    public const FILENAME = 'filename.yaml';

    private FileSource $fileSource;
    private GitSource $gitSource;

    protected function setUp(): void
    {
        parent::setUp();

        $fileSource = SourceOriginFactory::create(type: 'file');
        \assert($fileSource instanceof FileSource);
        $this->fileSource = $fileSource;

        $gitSource = SourceOriginFactory::create(type: 'git');
        \assert($gitSource instanceof GitSource);
        $this->gitSource = $gitSource;

        $this->gitSource->setLabel('git source label');
        $this->gitSource->setHostUrl('http://example.com/repo.git');
        $this->gitSource->setPath('/');
        $this->gitSource->setCredentials('');

        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);
        $sourceRepository->save($this->fileSource);
        $sourceRepository->save($this->gitSource);
    }

    public function testAddFileInvalidUser(): void
    {
        $response = $this->applicationClient->makeAddFileRequest(
            self::$authenticationConfiguration->getApiToken(self::USER_1_EMAIL),
            $this->fileSource->getId(),
            self::FILENAME,
            '- content'
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testRemoveFileInvalidUser(): void
    {
        $response = $this->applicationClient->makeRemoveFileRequest(
            self::$authenticationConfiguration->getApiToken(self::USER_1_EMAIL),
            $this->fileSource->getId(),
            self::FILENAME
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testReadFileInvalidUser(): void
    {
        $response = $this->applicationClient->makeRemoveFileRequest(
            self::$authenticationConfiguration->getApiToken(self::USER_1_EMAIL),
            $this->fileSource->getId(),
            self::FILENAME
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testGetSourceInvalidUser(): void
    {
        $response = $this->applicationClient->makeGetSourceRequest(
            self::$authenticationConfiguration->getApiToken(self::USER_1_EMAIL),
            $this->fileSource->getId()
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testUpdateSourceInvalidUser(): void
    {
        $response = $this->applicationClient->makeUpdateSourceRequest(
            self::$authenticationConfiguration->getApiToken(self::USER_1_EMAIL),
            $this->fileSource->getId(),
            [
                OriginSourceRequest::PARAMETER_TYPE => Type::FILE->value,
                'label' => 'non-empty label',
            ]
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testDeleteSourceInvalidUser(): void
    {
        $response = $this->applicationClient->makeDeleteSourceRequest(
            self::$authenticationConfiguration->getApiToken(self::USER_1_EMAIL),
            $this->fileSource->getId()
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }
}
