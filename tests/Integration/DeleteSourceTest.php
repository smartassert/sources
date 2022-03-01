<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Enum\Source\Type;
use App\Model\EntityId;
use App\Repository\SourceRepository;
use App\Services\Source\Store;
use App\Tests\Model\UserId;
use App\Tests\Services\EntityRemover;

class DeleteSourceTest extends AbstractIntegrationTest
{
    private SourceRepository $sourceRepository;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);
        $this->sourceRepository = $sourceRepository;

        $store = self::getContainer()->get(Store::class);
        \assert($store instanceof Store);
        $this->store = $store;

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
        }
    }

    public function testDeleteUnauthorizedUser(): void
    {
        $response = $this->client->makeDeleteSourceRequest($this->invalidToken, EntityId::create());

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    public function testDeleteInvalidSourceUser(): void
    {
        $source = new FileSource(UserId::create(), '');
        $this->store->add($source);

        $response = $this->client->makeDeleteSourceRequest($this->validToken, $source->getId());

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    /**
     * @dataProvider deleteSuccessDataProvider
     */
    public function testDeleteSuccess(SourceInterface $source, int $expectedRepositoryCount): void
    {
        $this->sourceUserIdMutator->setSourceUserId($source);

        $this->store->add($source);
        self::assertGreaterThan(0, $this->sourceRepository->count([]));

        $response = $this->client->makeDeleteSourceRequest($this->validToken, $source->getId());

        $this->responseAsserter->assertSuccessfulResponseWithNoBody($response);
        self::assertSame($expectedRepositoryCount, $this->sourceRepository->count([]));
    }

    /**
     * @return array<mixed>
     */
    public function deleteSuccessDataProvider(): array
    {
        return [
            Type::FILE->value => [
                'source' => new FileSource(self::AUTHENTICATED_USER_ID_PLACEHOLDER, 'label'),
                'expectedRepositoryCount' => 0,
            ],
            Type::GIT->value => [
                'source' => new GitSource(
                    self::AUTHENTICATED_USER_ID_PLACEHOLDER,
                    'https://example.com/repository.git'
                ),
                'expectedRepositoryCount' => 0,
            ],
            Type::RUN->value => [
                'source' => new RunSource(
                    new FileSource(self::AUTHENTICATED_USER_ID_PLACEHOLDER, 'label')
                ),
                'expectedRepositoryCount' => 1,
            ],
        ];
    }
}