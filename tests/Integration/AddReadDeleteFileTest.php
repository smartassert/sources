<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\FileSource;
use App\Model\EntityId;
use App\Services\Source\Store;
use App\Tests\Model\UserId;
use App\Tests\Services\EntityRemover;
use App\Validator\YamlFilenameConstraint;

class AddReadDeleteFileTest extends AbstractIntegrationTest
{
    private const FILENAME = 'filename.yaml';

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

    public function testAddFileUnauthorizedUser(): void
    {
        $response = $this->client->makeAddFileRequest(
            $this->invalidToken,
            $this->source->getId(),
            self::FILENAME,
            '- content'
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    public function testAddFileInvalidSourceUser(): void
    {
        $source = new FileSource(UserId::create(), '');
        $this->store->add($source);

        $response = $this->client->makeAddFileRequest(
            $this->validToken,
            $source->getId(),
            self::FILENAME,
            '- content'
        );

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    /**
     * @dataProvider addFileInvalidRequestDataProvider
     *
     * @param array<mixed> $expectedResponseData
     */
    public function testAddFileInvalidRequest(
        string $filename,
        string $content,
        array $expectedResponseData
    ): void {
        $response = $this->client->makeAddFileRequest(
            $this->validToken,
            $this->source->getId(),
            $filename,
            $content
        );

        $this->responseAsserter->assertInvalidRequestJsonResponse($response, $expectedResponseData);
    }

    /**
     * @return array<mixed>
     */
    public function addFileInvalidRequestDataProvider(): array
    {
        return [
            'name empty with .yaml extension, content non-empty' => [
                'filename' => '.yaml',
                'content' => 'non-empty value',
                'expectedResponseData' => $this->createExpectedInvalidFilenameResponseData(
                    YamlFilenameConstraint::MESSAGE_NAME_EMPTY
                ),
            ],
            'name contains backslash characters, content non-empty' => [
                'filename' => 'one-two-\\-three.yaml',
                'content' => 'non-empty value',
                'expectedResponseData' => $this->createExpectedInvalidFilenameResponseData(
                    YamlFilenameConstraint::MESSAGE_FILENAME_INVALID
                ),
            ],
            'name contains space characters, content non-empty' => [
                'filename' => 'one two three.yaml',
                'content' => 'non-empty value',
                'expectedResponseData' => $this->createExpectedInvalidFilenameResponseData(
                    YamlFilenameConstraint::MESSAGE_FILENAME_INVALID
                ),
            ],
            'name valid, content empty' => [
                'filename' => self::FILENAME,
                'content' => '',
                'expectedResponseData' => [
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            'content' => [
                                'value' => '',
                                'message' => 'File content must not be empty.',
                            ],
                        ],
                    ],
                ],
            ],
            'name valid, content invalid yaml' => [
                'filename' => self::FILENAME,
                'content' => "- item\ncontent",
                'expectedResponseData' => [
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            'content' => [
                                'value' => '',
                                'message' => 'Content must be valid YAML: Unable to parse at line 2 (near "content").',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testReadFileUnauthorizedUser(): void
    {
        $response = $this->client->makeRemoveFileRequest($this->invalidToken, EntityId::create(), self::FILENAME);

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    public function testReadFileInvalidSourceUser(): void
    {
        $source = new FileSource(UserId::create(), '');
        $this->store->add($source);

        $response = $this->client->makeReadFileRequest(
            $this->validToken,
            $source->getId(),
            self::FILENAME
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

    public function testRemoveFileUnauthorizedUser(): void
    {
        $response = $this->client->makeRemoveFileRequest(
            $this->invalidToken,
            $this->source->getId(),
            self::FILENAME
        );

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    public function testRemoveFileInvalidSourceUser(): void
    {
        $source = new FileSource(UserId::create(), '');
        $this->store->add($source);

        $response = $this->client->makeRemoveFileRequest($this->validToken, $source->getId(), self::FILENAME);

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    /**
     * @dataProvider yamlFileInvalidRequestDataProvider
     *
     * @param array<mixed> $expectedResponseData
     */
    public function testRemoveFileInvalidRequest(
        string $filename,
        array $expectedResponseData
    ): void {
        $response = $this->client->makeRemoveFileRequest($this->validToken, $this->source->getId(), $filename);

        $this->responseAsserter->assertInvalidRequestJsonResponse($response, $expectedResponseData);
    }

    public function testAddReadRemoveFileSuccess(): void
    {
        $initialContent = '- initial content';
        $updatedContent = '- updated content';

        $addResponse = $this->client->makeAddFileRequest(
            $this->validToken,
            $this->source->getId(),
            self::FILENAME,
            $initialContent
        );

        $this->responseAsserter->assertSuccessfulResponseWithNoBody($addResponse);

        $initialReadResponse = $this->client->makeReadFileRequest(
            $this->validToken,
            $this->source->getId(),
            self::FILENAME
        );

        $this->responseAsserter->assertReadSourceSuccessResponse($initialReadResponse, $initialContent);

        $updateResponse = $this->client->makeAddFileRequest(
            $this->validToken,
            $this->source->getId(),
            self::FILENAME,
            $updatedContent
        );

        $this->responseAsserter->assertSuccessfulResponseWithNoBody($updateResponse);

        $updatedReadResponse = $this->client->makeReadFileRequest(
            $this->validToken,
            $this->source->getId(),
            self::FILENAME
        );

        $this->responseAsserter->assertReadSourceSuccessResponse($updatedReadResponse, $updatedContent);

        $removeResponse = $this->client->makeRemoveFileRequest(
            $this->validToken,
            $this->source->getId(),
            self::FILENAME
        );

        $this->responseAsserter->assertSuccessfulResponseWithNoBody($removeResponse);

        $notFoundReadResponse = $this->client->makeReadFileRequest(
            $this->validToken,
            $this->source->getId(),
            self::FILENAME
        );

        $this->responseAsserter->assertNotFoundResponse($notFoundReadResponse);
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
