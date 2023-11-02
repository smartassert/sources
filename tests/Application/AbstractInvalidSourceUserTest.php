<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Repository\SourceRepository;
use App\Request\SuiteRequest;
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
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->fileSource->getId(),
            self::FILENAME,
            '- content'
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testRemoveFileInvalidUser(): void
    {
        $response = $this->applicationClient->makeRemoveFileRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->fileSource->getId(),
            self::FILENAME
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testReadFileInvalidUser(): void
    {
        $response = $this->applicationClient->makeRemoveFileRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->fileSource->getId(),
            self::FILENAME
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testGetSourceInvalidUser(): void
    {
        $response = $this->applicationClient->makeGetSourceRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->fileSource->getId()
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testDeleteSourceInvalidUser(): void
    {
        $response = $this->applicationClient->makeDeleteSourceRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->fileSource->getId()
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testListFileSourceFilenamesInvalidUser(): void
    {
        $response = $this->applicationClient->makeGetFileSourceFilenamesRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $this->fileSource->getId()
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testCreateSuiteInvalidUser(): void
    {
        $response = $this->applicationClient->makeCreateSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            [
                SuiteRequest::PARAMETER_SOURCE_ID => $this->fileSource->getId(),
                SuiteRequest::PARAMETER_LABEL => md5((string) rand()),
            ]
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }
}
