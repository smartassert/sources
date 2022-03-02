<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\SourceInterface;
use App\Enum\Source\Type;
use App\Model\EntityId;
use App\Request\GitSourceRequest;
use App\Request\SourceRequestInterface;
use App\Services\Source\Store;
use App\Tests\DataProvider\TestConstants;
use App\Tests\DataProvider\UpdateSourceSuccessDataProviderTrait;
use App\Tests\Model\UserId;
use App\Tests\Services\EntityRemover;

class UpdateSourceTest extends AbstractIntegrationTest
{
    use UpdateSourceSuccessDataProviderTrait;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $store = self::getContainer()->get(Store::class);
        \assert($store instanceof Store);
        $this->store = $store;

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
        }
    }

    public function testUpdateUnauthorizedUser(): void
    {
        $response = $this->client->makeUpdateSourceRequest($this->invalidToken, EntityId::create(), []);

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    public function testUpdateInvalidSourceUser(): void
    {
        $source = new FileSource(UserId::create(), '');
        $this->store->add($source);

        $response = $this->client->makeUpdateSourceRequest($this->validToken, $source->getId(), []);

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    /**
     * @dataProvider updateInvalidRequestDataProvider
     *
     * @param array<string, string> $payload
     * @param array<mixed>          $expectedResponseData
     */
    public function testUpdateInvalidRequest(
        SourceInterface $source,
        array $payload,
        array $expectedResponseData
    ): void {
        $this->sourceUserIdMutator->setSourceUserId($source);
        $this->store->add($source);

        $response = $this->client->makeUpdateSourceRequest($this->validToken, $source->getId(), $payload);

        $expectedResponseData = $this->sourceUserIdMutator->setSourceDataUserId($expectedResponseData);

        $this->responseAsserter->assertInvalidRequestJsonResponse($response, $expectedResponseData);
    }

    /**
     * @return array<mixed>
     */
    public function updateInvalidRequestDataProvider(): array
    {
        $userId = TestConstants::AUTHENTICATED_USER_ID_PLACEHOLDER;
        $hostUrl = 'https://example.com/repository.git';
        $path = '/';
        $credentials = md5((string) rand());

        $gitSource = new GitSource($userId, $hostUrl, $path, $credentials);

        return [
            Type::GIT->value . ' missing host url' => [
                'source' => $gitSource,
                'payload' => [
                    SourceRequestInterface::PARAMETER_TYPE => Type::GIT->value,
                    GitSourceRequest::PARAMETER_HOST_URL => '',
                    GitSourceRequest::PARAMETER_PATH => $path,
                ],
                'expectedResponseData' => [
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            'host-url' => [
                                'value' => '',
                                'message' => 'This value should not be blank.',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider updateSourceSuccessDataProvider
     *
     * @param array<string, string> $payload
     * @param array<mixed>          $expectedResponseData
     */
    public function testUpdateSuccess(
        SourceInterface $source,
        array $payload,
        array $expectedResponseData
    ): void {
        $this->sourceUserIdMutator->setSourceUserId($source);
        $this->store->add($source);

        $response = $this->client->makeUpdateSourceRequest($this->validToken, $source->getId(), $payload);

        $expectedResponseData = $this->sourceUserIdMutator->setSourceDataUserId($expectedResponseData);

        $this->responseAsserter->assertSuccessfulJsonResponse($response, $expectedResponseData);
    }
}
