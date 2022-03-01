<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\FileSource;
use App\Model\EntityId;
use App\Services\Source\Store;
use App\Tests\Model\UserId;
use App\Tests\Services\EntityRemover;
use App\Validator\YamlFilenameConstraint;

class ReadFileTest extends AbstractIntegrationTest
{
    private FileSource $source;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
        }

        $store = self::getContainer()->get(Store::class);
        \assert($store instanceof Store);
        $this->store = $store;

        $this->source = new FileSource($this->authenticationConfiguration->authenticatedUserId, '');
        $this->store->add($this->source);
    }

    public function testReadFileUnauthorizedUser(): void
    {
        $response = $this->client->makeRemoveFileRequest($this->invalidToken, EntityId::create(), 'filename.yaml');

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    public function testReadFileInvalidSourceUser(): void
    {
        $source = new FileSource(UserId::create(), '');
        $this->store->add($source);

        $response = $this->client->makeReadFileRequest(
            $this->validToken,
            $source->getId(),
            'filename.yaml'
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    /**
     * @dataProvider yamlFileInvalidRequestDataProvider
     *
     * @param array<mixed> $expectedResponseData
     */
    public function testReadFileInvalidRequest(
        string $filename,
        array $expectedResponseData
    ): void {
        $response = $this->client->makeReadFileRequest(
            $this->validToken,
            $this->source->getId(),
            $filename
        );

        $this->responseAsserter->assertInvalidRequestJsonResponse($response, $expectedResponseData);
    }

    public function testReadFileSuccess(): void
    {
        $filename = 'filename.yaml';
        $content = '- file content';

        $addFileResponse = $this->client->makeAddFileRequest(
            $this->authenticationConfiguration->validToken,
            $this->source->getId(),
            $filename,
            $content
        );

        $this->responseAsserter->assertSuccessfulResponseWithNoBody($addFileResponse);

        $response = $this->client->makeReadFileRequest(
            $this->validToken,
            $this->source->getId(),
            $filename
        );

        $this->responseAsserter->assertReadSourceSuccessResponse($response, $content);
    }

    /**
     * @return array<mixed>
     */
    public function yamlFileInvalidRequestDataProvider(): array
    {
        return [
            'name empty with .yaml extension' => [
                'filename' => '.yaml',
                'expectedResponseData' => $this->createExpectedInvalidFilenameResponseData(
                    YamlFilenameConstraint::MESSAGE_NAME_EMPTY
                ),
            ],
            'name contains backslash characters' => [
                'filename' => 'one-two-\\-three.yaml',
                'expectedResponseData' => $this->createExpectedInvalidFilenameResponseData(
                    YamlFilenameConstraint::MESSAGE_FILENAME_INVALID
                ),
            ],
            'name contains space characters' => [
                'filename' => 'one two three.yaml',
                'expectedResponseData' => $this->createExpectedInvalidFilenameResponseData(
                    YamlFilenameConstraint::MESSAGE_FILENAME_INVALID
                ),
            ],
        ];
    }

    /**
     * @return array<mixed>
     */
    private function createExpectedInvalidFilenameResponseData(string $message): array
    {
        return [
            'error' => [
                'type' => 'invalid_request',
                'payload' => [
                    'name' => [
                        'value' => '',
                        'message' => $message,
                    ],
                ],
            ],
        ];
    }
}
