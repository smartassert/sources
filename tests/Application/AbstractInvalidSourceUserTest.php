<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Tests\Model\UserId;

abstract class AbstractInvalidSourceUserTest extends AbstractApplicationTest
{
    public const FILENAME = 'filename.yaml';

    private FileSource $fileSource;
    private GitSource $gitSource;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileSource = new FileSource(UserId::create(), 'non-empty string');
        $this->store->add($this->fileSource);

        $this->gitSource = new GitSource(UserId::create(), 'http://example.com/repo.git', '/');
        $this->store->add($this->gitSource);
    }

    public function testAddFileInvalidUser(): void
    {
        $response = $this->applicationClient->makeAddFileRequest(
            self::$authenticationConfiguration->getValidApiToken(),
            $this->fileSource->getId(),
            self::FILENAME,
            '- content'
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testRemoveFileInvalidUser(): void
    {
        $response = $this->applicationClient->makeRemoveFileRequest(
            self::$authenticationConfiguration->getValidApiToken(),
            $this->fileSource->getId(),
            self::FILENAME
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testReadFileInvalidUser(): void
    {
        $response = $this->applicationClient->makeRemoveFileRequest(
            self::$authenticationConfiguration->getValidApiToken(),
            $this->fileSource->getId(),
            self::FILENAME
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testGetSourceInvalidUser(): void
    {
        $response = $this->applicationClient->makeGetSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(),
            $this->fileSource->getId()
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testUpdateFileSourceInvalidUser(): void
    {
        $response = $this->applicationClient->makeUpdateFileSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(),
            $this->fileSource->getId(),
            [
                'label' => 'non-empty label',
            ]
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testUpdateGitSourceInvalidUser(): void
    {
        $response = $this->applicationClient->makeUpdateGitSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(),
            $this->gitSource->getId(),
            [
                'label' => 'non-empty label',
                'host-url' => 'https://example.com/repository.git',
                'path' => '/',
            ]
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testDeleteSourceInvalidUser(): void
    {
        $response = $this->applicationClient->makeDeleteSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(),
            $this->fileSource->getId()
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testPrepareSourceInvalidUser(): void
    {
        $response = $this->applicationClient->makePrepareSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(),
            $this->fileSource->getId(),
            []
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testReadSourceInvalidUser(): void
    {
        $runSource = new RunSource($this->fileSource);
        $this->store->add($runSource);

        $response = $this->applicationClient->makeReadSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(),
            $runSource->getId()
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }
}
