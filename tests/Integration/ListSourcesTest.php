<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Enum\Source\Type;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use App\Request\SourceRequestInterface;
use App\Tests\Services\EntityRemover;

class ListSourcesTest extends AbstractIntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
        }
    }

    public function testListUnauthorizedUser(): void
    {
        $response = $this->applicationClient->makeListSourcesRequest($this->invalidToken);

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    public function testListSuccess(): void
    {
        $sourceIds = [];

        $createResponse = $this->applicationClient->makeCreateSourceRequest($this->validToken, [
            SourceRequestInterface::PARAMETER_TYPE => Type::GIT->value,
            GitSourceRequest::PARAMETER_HOST_URL => 'http://example.com/repository.git',
            GitSourceRequest::PARAMETER_PATH => '/without-credentials-path'
        ]);

        $createResponseData = json_decode($createResponse->getBody()->getContents(), true);
        $sourceIds[] = is_array($createResponseData) ? $createResponseData['id'] ?? null : null;

        $credentials = md5((string) rand());
        $createResponse = $this->applicationClient->makeCreateSourceRequest($this->validToken, [
            SourceRequestInterface::PARAMETER_TYPE => Type::GIT->value,
            GitSourceRequest::PARAMETER_HOST_URL => 'http://example.com/repository-with-credentials.git',
            GitSourceRequest::PARAMETER_PATH => '/with-credentials-path',
            GitSourceRequest::PARAMETER_CREDENTIALS => $credentials,
        ]);

        $createResponseData = json_decode($createResponse->getBody()->getContents(), true);
        $sourceIds[] = is_array($createResponseData) ? $createResponseData['id'] ?? null : null;

        $label = 'file source label';
        $createResponse = $this->applicationClient->makeCreateSourceRequest($this->validToken, [
            SourceRequestInterface::PARAMETER_TYPE => Type::FILE->value,
            FileSourceRequest::PARAMETER_LABEL => $label
        ]);

        $createResponseData = json_decode($createResponse->getBody()->getContents(), true);
        $sourceIds[] = is_array($createResponseData) ? $createResponseData['id'] ?? null : null;

        $listResponse = $this->applicationClient->makeListSourcesRequest(
            $this->validToken
        );

        $expected = [
            [
                'id' => $sourceIds[0],
                'user_id' => $this->authenticationConfiguration->authenticatedUserId,
                'type' => Type::GIT->value,
                'host_url' => 'http://example.com/repository.git',
                'path' => '/without-credentials-path',
                'has_credentials' => false,
            ],
            [
                'id' => $sourceIds[1],
                'user_id' => $this->authenticationConfiguration->authenticatedUserId,
                'type' => Type::GIT->value,
                'host_url' => 'http://example.com/repository-with-credentials.git',
                'path' => '/with-credentials-path',
                'has_credentials' => true,
            ],
            [
                'id' => $sourceIds[2],
                'user_id' => $this->authenticationConfiguration->authenticatedUserId,
                'type' => Type::FILE->value,
                'label' => $label,
            ],
        ];

        $this->responseAsserter->assertSuccessfulJsonResponse($listResponse, $expected);
    }
}
