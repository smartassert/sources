<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\FileSource;
use App\Entity\RunSource;
use App\Enum\RunSource\State;
use App\Enum\Source\Type;
use App\Model\EntityId;
use App\Services\Source\Store;
use App\Tests\Model\UserId;
use App\Tests\Services\EntityRemover;

class ReadSourceTest extends AbstractIntegrationTest
{
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

    public function testReadUnauthorizedUser(): void
    {
        $response = $this->client->makeReadSourceRequest($this->invalidToken, EntityId::create());

        $this->responseAsserter->assertUnauthorizedResponse($response);
    }

    public function testReadInvalidSourceUser(): void
    {
        $source = new RunSource(new FileSource(UserId::create(), ''));
        $this->store->add($source);

        $response = $this->client->makeReadSourceRequest($this->validToken, $source->getId());

        $this->responseAsserter->assertForbiddenResponse($response);
    }

    public function testReadSuccess(): void
    {
        $fileSource = new FileSource($this->authenticationConfiguration->authenticatedUserId, 'file source label');
        $this->store->add($fileSource);

        $filename = 'filename.yaml';
        $content = '- file content';

        $addFileResponse = $this->client->makeAddFileRequest(
            $this->authenticationConfiguration->validToken,
            $fileSource->getId(),
            $filename,
            $content
        );

        $this->responseAsserter->assertSuccessfulResponseWithNoBody($addFileResponse);

        $prepareResponse = $this->client->makePrepareSourceRequest(
            $this->authenticationConfiguration->validToken,
            $fileSource->getId(),
            []
        );

        $this->responseAsserter->assertPrepareSourceSuccessResponseWithUnknownData($prepareResponse);

        $responseData = json_decode($prepareResponse->getBody()->getContents(), true);
        self::assertIsArray($responseData);
        self::assertArrayHasKey('id', $responseData);

        $runSourceId = $responseData['id'];

        $this->responseAsserter->assertPrepareSourceSuccessResponse($prepareResponse, [
            'id' => $runSourceId,
            'user_id' => $fileSource->getUserId(),
            'type' => Type::RUN->value,
            'parent' => $fileSource->getId(),
            'parameters' => [],
            'state' => State::REQUESTED->value,
        ]);

        $this->waitUntilSourceIsPrepared($runSourceId);

        $readResponse = $this->client->makeReadSourceRequest(
            $this->authenticationConfiguration->validToken,
            $runSourceId
        );

        $expectedContentHash = md5($content);
        $expectedReadResponseBody = <<< EOF
            ---
            path: "{$filename}"
            content_hash: "{$expectedContentHash}"
            ...
            ---
            {$content}
            ...
            EOF;

        $this->responseAsserter->assertReadSourceSuccessResponse($readResponse, $expectedReadResponseBody);
    }

    private function waitUntilSourceIsPrepared(string $runSourceId): void
    {
        $timeout = 30000;
        $duration = 0;
        $period = 1000;
        $state = null;

        while (State::PREPARED->value !== $state) {
            $getResponse = $this->client->makeGetSourceRequest(
                $this->authenticationConfiguration->validToken,
                $runSourceId
            );

            if (200 === $getResponse->getStatusCode()) {
                $responseData = json_decode($getResponse->getBody()->getContents(), true);

                if (is_array($responseData)) {
                    $state = $responseData['state'] ?? null;
                }

                if (State::PREPARED->value !== $state) {
                    $duration += $period;

                    if ($duration >= $timeout) {
                        throw new \RuntimeException('Timed out waiting for "' . $runSourceId . '" to prepare');
                    }

                    usleep($period);
                }
            }
        }
    }
}
